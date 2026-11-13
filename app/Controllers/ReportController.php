<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\Report;
use App\Models\Video;
use App\Models\Comment;

class ReportController extends BaseController
{
    private const REASONS = ['spam', 'insult', 'copyright', 'inappropriate', 'other'];

    public function store(Request $request): void
    {
        $user = $this->user();
        if (!$user) {
            $this->json(['error' => 'auth_required'], 401);
        }
        $reason = $request->post('reason', '');
        $details = trim((string)$request->post('details', ''));
        $videoId = $request->post('video_id', '');
        $commentId = $request->post('comment_id', '');

        $validator = new Validator();
        $errors = $validator->validate(['reason' => $reason], ['reason' => 'required']);
        if (!in_array($reason, self::REASONS, true)) {
            $errors['reason'] = 'Недопустимая причина.';
        }
        if (($videoId === '' && $commentId === '') || ($videoId !== '' && $commentId !== '')) {
            $errors['target'] = 'Укажите объект жалобы.';
        }
        if (!empty($errors)) {
            $this->json(['error' => 'Некорректная жалоба.'], 422);
        }

        $videoId = $videoId !== '' ? (int)$videoId : null;
        $commentId = $commentId !== '' ? (int)$commentId : null;
        if ($videoId && !Video::findById($videoId)) {
            $this->json(['error' => 'not_found'], 404);
        }
        if ($commentId && !Comment::findById($commentId)) {
            $this->json(['error' => 'not_found'], 404);
        }

        Report::create([
            'reporter_user_id' => (int)$user['id'],
            'video_id' => $videoId,
            'comment_id' => $commentId,
            'reason' => $reason,
            'details' => $details === '' ? null : $details,
        ]);
        $this->json(['ok' => true, 'message' => 'Жалоба отправлена. Спасибо за заботу о сообществе.']);
    }
}
