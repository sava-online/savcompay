<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Env;
use App\Models\Video;
use App\Models\Tag;
use App\Services\AiMetadataService;
use App\Services\AiModerationService;
use App\Services\AiSummaryService;
use App\Services\AiService;
use App\Services\RateLimiter;

class AiApiController extends BaseController
{
    private function input(Request $request): array
    {
        return $request->isJson() ? $request->json() : $request->all();
    }

    private function userId(): ?int
    {
        $user = $this->user();
        return $user ? (int)$user['id'] : null;
    }

    public function generateMetadata(Request $request): void
    {
        $in = $this->input($request);
        $payload = [
            'title'       => trim((string)($in['title'] ?? '')),
            'description' => trim((string)($in['description'] ?? '')),
            'category'    => $in['category'] ?? '',
            'tags'        => $in['tags'] ?? [],
        ];
        if (is_string($payload['tags'])) {
            $payload['tags'] = array_map('trim', explode(',', $payload['tags']));
        }
        $this->json(AiMetadataService::generate($payload, $this->userId()));
    }

    public function moderate(Request $request): void
    {
        $in = $this->input($request);
        $text = trim((string)($in['text'] ?? ''));
        $context = $in['context'] ?? 'comment';
        $this->json(AiModerationService::moderate($text, (string)$context, $this->userId()));
    }

    public function summarize(Request $request): void
    {
        $in = $this->input($request);
        $videoId = (int)($in['video_id'] ?? $request->params['video_id'] ?? 0);
        $video = Video::withRelations($videoId);
        if (!$video) {
            $this->json(['error' => 'not_found'], 404);
        }
        $tags = Tag::forVideo($videoId);
        $data = [
            'title'       => $video['title'],
            'description' => $video['description'] ?? '',
            'category'     => $video['category_name'] ?? '',
            'tags'        => array_column($tags, 'name'),
        ];
        $this->json(AiSummaryService::summarize($data, $this->userId()));
    }

    public function suggestTags(Request $request): void
    {
        $in = $this->input($request);
        $title = trim((string)($in['title'] ?? ''));
        $description = trim((string)($in['description'] ?? ''));
        $ai = new AiService($this->userId());
        $this->json($ai->suggestTags($title, $description));
    }

    public function search(Request $request): void
    {
        $in = $this->input($request);
        $query = trim((string)($in['query'] ?? ''));
        $ai = new AiService($this->userId());
        $this->json($ai->smartSearch($query));
    }

    public function limits(Request $request): void
    {
        $userId = $this->userId();
        $enabled = Env::bool('ENABLE_AI', true);
        $limit = $userId ? Env::int('AI_DAILY_LIMIT_AUTH', 100) : Env::int('AI_DAILY_LIMIT_GUEST', 10);
        $key = $userId ? 'ai:u:' . $userId : 'ai:guest:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $this->json([
            'enabled'   => $enabled,
            'limit'     => $limit,
            'remaining' => RateLimiter::remaining($key, $limit),
        ]);
    }
}
