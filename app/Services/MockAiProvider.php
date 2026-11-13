<?php
namespace App\Services;

/**
 * Mock provider. Works without any API key and returns safe, deterministic
 * JSON responses. Used for local development and when ENABLE_AI is off-fallback.
 */
class MockAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function generate(string $system, string $user, string $task = ''): string
    {
        $input = self::extractInput($user);
        return match ($task) {
            'metadata'      => $this->metadata($input),
            'moderate'      => $this->moderate($input),
            'summarize'     => $this->summarize($input),
            'suggest_tags'  => $this->suggestTags($input),
            'search'        => $this->search($input),
            default         => json_encode(['note' => 'mock response']),
        };
    }

    private function metadata(array $in): string
    {
        $title = isset($in['title']) ? ucfirst(mb_strtolower(trim($in['title']))) : 'Без названия';
        $desc  = $in['description'] ?? '';
        $tags  = $in['tags'] ?? [];
        $short = $desc !== '' ? mb_substr(preg_replace('/\s+/', ' ', $desc), 0, 160) : 'Спокойное видео в стиле GlassTube.';
        return json_encode([
            'title'         => $title,
            'description'   => $desc,
            'tags'          => array_slice(array_map('trim', (array)$tags), 0, 10),
            'short_summary' => $short,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function moderate(array $in): string
    {
        $text = mb_strtolower((string)($in['text'] ?? ''));
        $blocked = ['spam', 'http://', 'https://', 'купить', 'casino', 'xxx', 'сука', 'идиот'];
        $flagged = false;
        foreach ($blocked as $w) {
            if (str_contains($text, $w)) {
                $flagged = true;
                break;
            }
        }
        return json_encode([
            'safe'       => !$flagged,
            'category'   => $flagged ? 'spam' : 'ok',
            'confidence' => $flagged ? 0.82 : 0.95,
            'reason'     => $flagged ? 'Обнаружены признаки спама или ссылок.' : '',
        ], JSON_UNESCAPED_UNICODE);
    }

    private function summarize(array $in): string
    {
        $title = $in['title'] ?? 'Видео';
        $desc  = $in['description'] ?? '';
        return json_encode([
            'summary' => "Обзор видео «{$title}». " . ($desc !== '' ? mb_substr($desc, 0, 200) : 'Описание отсутствует.'),
            'topics' => array_slice(preg_split('/\s*,\s*/', $in['tags'] ?? ''), 0, 6),
            'chapters' => [
                ['time' => 0, 'title' => 'Вступление'],
                ['time' => 30, 'title' => 'Основная часть'],
                ['time' => 60, 'title' => 'Заключение'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function suggestTags(array $in): string
    {
        $text = mb_strtolower(($in['title'] ?? '') . ' ' . ($in['description'] ?? ''));
        $words = preg_split('/\s+/', preg_replace('/[^a-zа-яё0-9\s]/iu', ' ', $text));
        $stop = ['и','в','на','с','по','для','это','как','что','the','a','of','to','is'];
        $freq = [];
        foreach (array_filter($words) as $w) {
            if (mb_strlen($w) < 4 || in_array($w, $stop, true)) {
                continue;
            }
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }
        arsort($freq);
        $tags = array_slice(array_keys($freq), 0, 6);
        return json_encode(['tags' => $tags], JSON_UNESCAPED_UNICODE);
    }

    private function search(array $in): string
    {
        $q = $in['query'] ?? '';
        return json_encode([
            'query'       => $q,
            'filters'     => ['sort' => 'relevance'],
            'explanation' => 'Уточнённый поисковый запрос на основе вашего вопроса.',
        ], JSON_UNESCAPED_UNICODE);
    }

    /** Pull the JSON object embedded in the user prompt (the input payload). */
    private static function extractInput(string $user): array
    {
        $json = AiService::extractJson($user);
        return is_array($json) ? $json : [];
    }
}
