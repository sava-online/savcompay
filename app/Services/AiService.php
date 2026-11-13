<?php
namespace App\Services;

use App\Core\Env;
use App\Models\AiCache;
use App\Models\AiRequest;

/**
 * Central AI service: provider selection, caching, rate limiting, logging,
 * JSON validation, and safe fallbacks. Browser never calls providers directly.
 */
class AiService
{
    private ?int $userId;
    private string $providerName;
    private string $model;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
        $this->providerName = Env::get('AI_PROVIDER', 'mock');
        $this->model = Env::get('AI_MODEL', '') ?: 'mock-model';
    }

    /** Extract the first JSON object from text, stripping markdown fences. */
    public static function extractJson(string $text): ?array
    {
        $text = trim($text);
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $text, $m)) {
            $text = $m[1];
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $dec = json_decode(substr($text, $start, $end - $start + 1), true);
            if (is_array($dec)) {
                return $dec;
            }
        }
        $dec = json_decode(trim($text), true);
        return is_array($dec) ? $dec : null;
    }

    public function isEnabled(): bool
    {
        return Env::bool('ENABLE_AI', true);
    }

    private function provider(): AiProvider
    {
        return match ($this->providerName) {
            'openrouter', 'groq', 'custom_openai_compatible' =>
                new OpenAiCompatibleProvider(
                    Env::get('AI_BASE_URL', ''),
                    Env::get('AI_API_KEY', ''),
                    Env::get('AI_MODEL', '')
                ),
            default => new MockAiProvider(),
        };
    }

    private function call(string $task, string $system, string $user): array
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('ai_disabled');
        }
        $key = $this->userId
            ? 'ai:u:' . $this->userId
            : 'ai:guest:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $limit = $this->userId
            ? Env::int('AI_DAILY_LIMIT_AUTH', 100)
            : Env::int('AI_DAILY_LIMIT_GUEST', 10);

        if (!RateLimiter::hit($key, $limit)) {
            throw new \RuntimeException('rate_limit');
        }

        $provider = $this->provider();
        $hash = sha1($task . $system . $user);
        $cache = AiCache::get($hash);
        if ($cache) {
            $dec = json_decode($cache['response_json'], true);
            if (is_array($dec)) {
                return $dec;
            }
        }

        $start = microtime(true);
        $raw = $provider->generate($system, $user, $task);
        $ms = (int)((microtime(true) - $start) * 1000);

        $data = self::extractJson($raw);
        if ($data === null) {
            AiRequest::log([
                'user_id' => $this->userId, 'endpoint' => $task, 'provider' => $provider->name(),
                'model' => $this->model, 'input_hash' => $hash, 'status' => 'error',
                'error_message' => 'invalid_json', 'response_time_ms' => $ms,
            ]);
            throw new \RuntimeException('invalid_json');
        }

        AiCache::set($hash, $provider->name(), $this->model,
            json_encode(['system' => $system, 'user' => $user]), json_encode($data));
        AiRequest::log([
            'user_id' => $this->userId, 'endpoint' => $task, 'provider' => $provider->name(),
            'model' => $this->model, 'input_hash' => $hash, 'status' => 'ok', 'response_time_ms' => $ms,
        ]);

        return $data;
    }

    public function generateMetadata(array $input): array
    {
        $system = "Вы — редактор видеохостинга GlassTube. Улучшите метаданные. Верните ТОЛЬКО JSON: "
            . "{\"title\":string,\"description\":string,\"tags\":[string],\"short_summary\":string}.";
        try {
            return $this->normalizeMetadata($this->call('metadata', $system, json_encode($input, JSON_UNESCAPED_UNICODE)));
        } catch (\Throwable $e) {
            return $this->fallbackMetadata($input);
        }
    }

    public function moderate(string $text, string $context = 'comment'): array
    {
        $system = "Вы — модератор комментариев GlassTube. Оцените текст. Верните ТОЛЬКО JSON: "
            . "{\"safe\":bool,\"category\":string,\"confidence\":float,\"reason\":string}. "
            . "category ∈ ok|spam|insult|threat|adult|copyright|other.";
        try {
            return $this->normalizeModeration($this->call('moderate', $system,
                json_encode(['text' => $text, 'context' => $context], JSON_UNESCAPED_UNICODE)));
        } catch (\Throwable $e) {
            return ['safe' => true, 'category' => 'ok', 'confidence' => 0.0,
                'reason' => '', '_fallback' => true, '_error' => $e->getMessage()];
        }
    }

    public function summarize(array $video): array
    {
        $system = "Вы — помощник GlassTube. Суммируйте видео по метаданным. Верните ТОЛЬКО JSON: "
            . "{\"summary\":string,\"topics\":[string],\"chapters\":[{\"time\":int seconds,\"title\":string}]}.";
        try {
            $out = $this->call('summarize', $system, json_encode($video, JSON_UNESCAPED_UNICODE));
            $out['_fallback'] = false;
            return $out;
        } catch (\Throwable $e) {
            return [
                'summary' => 'ИИ-помощник временно недоступен. ' .
                    (!empty($video['description']) ? mb_substr($video['description'], 0, 240) : ''),
                'topics' => $video['tags'] ?? [],
                'chapters' => [],
                '_fallback' => true,
                '_error' => $e->getMessage(),
            ];
        }
    }

    public function suggestTags(string $title, string $description): array
    {
        $system = "Предложите теги для видео. Верните ТОЛЬКО JSON: {\"tags\":[string]}.";
        try {
            $out = $this->call('suggest_tags', $system,
                json_encode(['title' => $title, 'description' => $description], JSON_UNESCAPED_UNICODE));
            $tags = isset($out['tags']) && is_array($out['tags']) ? array_slice($out['tags'], 0, 10) : [];
            return ['tags' => $tags, '_fallback' => false];
        } catch (\Throwable $e) {
            return ['tags' => [], '_fallback' => true, '_error' => $e->getMessage()];
        }
    }

    public function smartSearch(string $query): array
    {
        $system = "Улучшите поисковый запрос. Верните ТОЛЬКО JSON: "
            . "{\"query\":string,\"filters\":object,\"explanation\":string}.";
        try {
            $out = $this->call('search', $system, json_encode(['query' => $query], JSON_UNESCAPED_UNICODE));
            $out['_fallback'] = false;
            return $out;
        } catch (\Throwable $e) {
            return ['query' => $query, 'filters' => [], 'explanation' => '',
                '_fallback' => true, '_error' => $e->getMessage()];
        }
    }

    // ---- normalizers / fallbacks ----

    private function normalizeMetadata(array $d): array
    {
        return [
            'title'         => is_string($d['title'] ?? '') ? $d['title'] : '',
            'description'   => is_string($d['description'] ?? '') ? $d['description'] : '',
            'tags'          => isset($d['tags']) && is_array($d['tags']) ? array_slice($d['tags'], 0, 10) : [],
            'short_summary' => is_string($d['short_summary'] ?? '') ? $d['short_summary'] : '',
            '_fallback'     => false,
        ];
    }

    private function fallbackMetadata(array $input): array
    {
        $desc = $input['description'] ?? '';
        return [
            'title'         => $input['title'] ?? '',
            'description'   => $desc,
            'tags'          => $input['tags'] ?? [],
            'short_summary' => $desc !== '' ? mb_substr($desc, 0, 160) : '',
            '_fallback'     => true,
        ];
    }

    private function normalizeModeration(array $d): array
    {
        return [
            'safe'       => (bool)($d['safe'] ?? true),
            'category'   => is_string($d['category'] ?? '') ? $d['category'] : 'ok',
            'confidence' => is_numeric($d['confidence'] ?? 0) ? (float)$d['confidence'] : 0.0,
            'reason'     => is_string($d['reason'] ?? '') ? $d['reason'] : '',
            '_fallback'  => false,
        ];
    }
}
