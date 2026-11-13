<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Core\Session;
use App\Core\Env;
use App\Models\Category;
use App\Services\VideoService;
use App\Services\AiModerationService;

class UploadController extends BaseController
{
    public function show(Request $request): void
    {
        $categories = Category::all();
        echo $this->render('upload', [
            'title'      => 'Загрузка видео',
            'page_title' => 'Загрузка',
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): void
    {
        $user = $this->user();
        $title = trim((string)$request->post('title', ''));
        $description = trim((string)$request->post('description', ''));
        $visibility = $request->post('visibility', 'public');
        $categoryId = $request->post('category_id', '');
        $tags = $request->post('tags', '');
        $acceptRights = (string)$request->post('accept_rights', '');

        $validator = new Validator();
        $errors = $validator->validate(['title' => $title], ['title' => 'required|max:160']);
        if (!in_array($visibility, ['public', 'unlisted', 'private'], true)) {
            $errors['visibility'] = 'Недопустимая видимость.';
        }
        if ($acceptRights !== '1') {
            $errors['accept_rights'] = 'Подтвердите права на публикацию.';
        }
        if (!$request->hasFile('video_file')) {
            $errors['video_file'] = 'Выберите видеофайл.';
        }

        if (!empty($errors)) {
            set_flash('error', 'Проверьте форму загрузки.');
            Session::set('old_input', [
                'title' => $title, 'description' => $description,
                'visibility' => $visibility, 'category_id' => $categoryId, 'tags' => $tags,
            ]);
            $this->redirect(url('upload'));
        }

        // Optional: AI moderation of description before publishing.
        $aiStatus = null;
        if (Env::bool('ENABLE_AI', true)) {
            $mod = AiModerationService::moderate($description ?: $title, 'video_description', (int)$user['id']);
            $aiStatus = $mod['safe'] ? 'ok' : ($mod['category'] ?? 'flagged');
        }

        try {
            $videoId = VideoService::handleUpload(
                [
                    'title'       => $title,
                    'description' => $description,
                    'visibility'  => $visibility,
                    'category_id' => $categoryId === '' ? null : (int)$categoryId,
                    'tags'        => $tags,
                ],
                $request->file('video_file'),
                $user
            );
            if ($aiStatus !== null && $aiStatus !== 'ok') {
                \App\Models\Video::update($videoId, ['ai_moderation_status' => $aiStatus]);
            }
            if ($request->isAjax()) {
                $this->json(['ok' => true, 'redirect' => url('watch/' . $videoId)]);
            }
            set_flash('success', 'Видео загружено и обрабатывается. Скоро оно появится в каталоге.');
            $this->redirect(url('watch/' . $videoId));
        } catch (\Throwable $e) {
            if ($request->isAjax()) {
                $this->json(['error' => 'Ошибка загрузки: ' . $e->getMessage()], 400);
            }
            set_flash('error', 'Ошибка загрузки: ' . ($e->getMessage()));
            Session::set('old_input', [
                'title' => $title, 'description' => $description,
                'visibility' => $visibility, 'category_id' => $categoryId, 'tags' => $tags,
            ]);
            $this->redirect(url('upload'));
        }
    }
}
