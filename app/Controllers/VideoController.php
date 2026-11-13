<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Core\Session;
use App\Models\Video;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\Like;
use App\Models\Subscription;
use App\Models\Playlist;
use App\Models\WatchHistory;
use App\Core\Database;
use App\Services\StreamingService;
use App\Services\StorageService;
use App\Controllers\ErrorController;

class VideoController extends BaseController
{
    public function watch(Request $request): void
    {
        $id = (int)$request->params['video_id'];
        $video = Video::withRelations($id);
        if (!$video) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        if (!StreamingService::canAccess($video, $this->user())) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }

        // Count a view once per video per session.
        $viewed = Session::get('viewed_videos', []);
        if (!in_array($id, $viewed, true)) {
            Video::incrementViews($id);
            $viewed[] = $id;
            Session::set('viewed_videos', $viewed);
        }

        $user = $this->user();
        $userId = $user ? (int)$user['id'] : null;

        $page = max(1, (int)$request->get('cpage', 1));
        $comments = Comment::listByVideo($id, $page, 20);
        $tags = Tag::forVideo($id);

        $liked = $userId ? Like::exists($userId, $id) : false;
        $saved = false;
        $subscribed = false;
        if ($userId) {
            $wl = Playlist::ensureWatchLater($userId);
            $saved = (bool)\App\Core\Database::instance()->column(
                "SELECT 1 FROM playlist_items WHERE playlist_id = ? AND video_id = ?",
                [$wl, $id]
            );
            $subscribed = Subscription::exists($userId, (int)$video['channel_id']);
        }

        $similar = [];
        if ($video['category_id']) {
            $similar = Video::list([
                'category_id' => $video['category_id'],
                'visibility'   => 'public',
                'sort'         => 'popular',
                'limit'        => 7,
            ]);
            $similar = array_filter($similar, fn($v) => (int)$v['id'] !== $id);
            $similar = array_slice($similar, 0, 6);
        }

        echo $this->render('watch', [
            'title'      => e($video['title']),
            'page_title' => e($video['title']),
            'video'      => $video,
            'comments'   => $comments,
            'comment_page' => $page,
            'tags'       => $tags,
            'liked'      => $liked,
            'saved'      => $saved,
            'subscribed' => $subscribed,
            'similar'    => $similar,
            'is_owner'   => $userId !== null && $userId === (int)$video['user_id'],
        ]);
    }

    public function like(Request $request): void
    {
        $userId = $this->requireUserId();
        $id = (int)$request->params['video_id'];
        $video = Video::findById($id);
        if (!$video) {
            $this->json(['error' => 'not_found'], 404);
        }
        $liked = Like::toggle($userId, $id);
        $count = (int)\App\Core\Database::instance()->column("SELECT like_count FROM videos WHERE id = ?", [$id]);
        $this->json(['liked' => $liked, 'count' => $count]);
    }

    public function save(Request $request): void
    {
        $userId = $this->requireUserId();
        $id = (int)$request->params['video_id'];
        $video = Video::findById($id);
        if (!$video) {
            $this->json(['error' => 'not_found'], 404);
        }
        $wl = Playlist::ensureWatchLater($userId);
        if (\App\Core\Database::instance()->column("SELECT 1 FROM playlist_items WHERE playlist_id = ? AND video_id = ?", [$wl, $id])) {
            Playlist::removeItem($wl, $id);
            $saved = false;
        } else {
            Playlist::addItem($wl, $id);
            $saved = true;
        }
        $this->json(['saved' => $saved]);
    }

    public function progress(Request $request): void
    {
        $userId = $this->requireUserId();
        $id = (int)$request->params['video_id'];
        $seconds = max(0, (int)$request->post('seconds', 0));
        WatchHistory::upsert($userId, $id, $seconds);
        $this->json(['ok' => true]);
    }

    public function edit(Request $request): void
    {
        $id = (int)$request->params['video_id'];
        $video = Video::findById($id);
        if (!$video || !$this->owns($video)) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        $categories = \App\Models\Category::all();
        echo $this->render('video_edit', [
            'title'  => 'Редактирование видео',
            'video'  => $video,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request): void
    {
        $id = (int)$request->params['video_id'];
        $video = Video::findById($id);
        if (!$video || !$this->owns($video)) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        $title = trim((string)$request->post('title', ''));
        $description = trim((string)$request->post('description', ''));
        $visibility = $request->post('visibility', 'public');
        $categoryId = $request->post('category_id', '');

        $validator = new Validator();
        $errors = $validator->validate(['title' => $title], ['title' => 'required|max:160']);
        if (!in_array($visibility, ['public', 'unlisted', 'private'], true)) {
            $errors['visibility'] = 'Недопустимая видимость.';
        }
        if (!empty($errors)) {
            set_flash('error', 'Проверьте название видео.');
            $this->redirect(url('video/' . $id . '/edit'));
        }

        Video::update($id, [
            'title'       => $title,
            'description' => $description === '' ? null : $description,
            'visibility'  => $visibility,
            'category_id' => $categoryId === '' ? null : (int)$categoryId,
        ]);
        set_flash('success', 'Видео обновлено.');
        $this->redirect(url('watch/' . $id));
    }

    public function delete(Request $request): void
    {
        $id = (int)$request->params['video_id'];
        $video = Video::findById($id);
        if (!$video || !$this->owns($video)) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        StorageService::delete($video['storage_path']);
        Video::delete($id);
        set_flash('success', 'Видео удалено.');
        $this->redirect(url(''));
    }

    private function requireUserId(): int
    {
        $user = $this->user();
        if (!$user) {
            $this->json(['error' => 'auth_required'], 401);
        }
        return (int)$user['id'];
    }

    private function owns(array $video): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        return (int)$user['id'] === (int)$video['user_id'] || ($user['role'] ?? '') === 'admin';
    }
}
