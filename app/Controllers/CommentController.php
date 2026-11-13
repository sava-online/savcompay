<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Core\Env;
use App\Models\Comment;
use App\Models\Video;
use App\Models\Channel;
use App\Services\AiModerationService;

class CommentController extends BaseController
{
    public function add(Request $request): void
    {
        $user = $this->user();
        if (!$user) {
            $this->json(['error' => 'auth_required'], 401);
        }
        $videoId = (int)$request->params['video_id'];
        $video = Video::findById($videoId);
        if (!$video) {
            $this->json(['error' => 'not_found'], 404);
        }

        $body = trim((string)$request->post('body', ''));
        $parentId = $request->post('parent_id', '');
        $parentId = $parentId !== '' ? (int)$parentId : null;

        $validator = new Validator();
        $errors = $validator->validate(['body' => $body], ['body' => 'required|max:2000']);
        if (!empty($errors)) {
            $this->json(['error' => $errors['body']], 422);
        }

        // AI moderation (safe fallback keeps comment visible).
        $status = Comment::STATUS_VISIBLE;
        if (Env::bool('ENABLE_AI', true)) {
            $mod = AiModerationService::moderate($body, 'comment', (int)$user['id']);
            if (empty($mod['safe'])) {
                $status = AiModerationService::statusFor($mod);
            }
        }

        $commentId = Comment::add($videoId, (int)$user['id'], $body, $status === Comment::STATUS_VISIBLE ? $parentId : null);
        Video::update($videoId, ['comment_count' => Comment::countByVideo($videoId)]);

        $this->json([
            'ok'     => true,
            'status' => $status,
            'id'     => $commentId,
            'hidden' => $status !== Comment::STATUS_VISIBLE,
            'message' => $status !== Comment::STATUS_VISIBLE
                ? 'Комментарий скрыт автоматической модерацией.'
                : '',
        ]);
    }

    public function delete(Request $request): void
    {
        $user = $this->user();
        if (!$user) {
            $this->json(['error' => 'auth_required'], 401);
        }
        $commentId = (int)$request->params['comment_id'];
        $comment = Comment::findById($commentId);
        if (!$comment) {
            $this->json(['error' => 'not_found'], 404);
        }

        $video = Video::findById($comment['video_id']);
        $channel = $video ? Channel::findByUserId((int)$video['user_id']) : null;
        $isAuthor = (int)$user['id'] === (int)$comment['user_id'];
        $isChannelOwner = $channel && (int)$user['id'] === (int)$channel['user_id'];
        $isAdmin = ($user['role'] ?? '') === 'admin';

        if (!$isAuthor && !$isChannelOwner && !$isAdmin) {
            $this->json(['error' => 'forbidden'], 403);
        }

        Comment::delete($commentId);
        if ($video) {
            Video::update($video['id'], ['comment_count' => Comment::countByVideo($video['id'])]);
        }
        $this->json(['ok' => true]);
    }
}
