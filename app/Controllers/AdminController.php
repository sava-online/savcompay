<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Env;
use App\Core\Session;
use App\Models\User;
use App\Models\Video;
use App\Models\Comment;
use App\Models\Report;
use App\Models\AiRequest;
use App\Models\Setting;
use App\Services\StorageService;

class AdminController extends BaseController
{
    public function dashboard(Request $request): void
    {
        $db = \App\Core\Database::instance();
        $stats = [
            'users'     => (int)$db->column("SELECT COUNT(*) FROM users"),
            'videos'    => (int)$db->column("SELECT COUNT(*) FROM videos"),
            'comments'  => (int)$db->column("SELECT COUNT(*) FROM comments"),
            'reports'   => (int)$db->column("SELECT COUNT(*) FROM reports WHERE status = 'open'"),
            'ai'        => (int)$db->column("SELECT COUNT(*) FROM ai_requests"),
        ];
        echo $this->render('admin/dashboard', [
            'title' => 'Админ-панель',
            'page_title' => 'Дашборд',
            'stats' => $stats,
        ]);
    }

    public function users(Request $request): void
    {
        $users = \App\Core\Database::instance()->fetchAll(
            "SELECT u.*, (SELECT COUNT(*) FROM videos v WHERE v.user_id = u.id) AS video_count
             FROM users u ORDER BY u.created_at DESC"
        );
        echo $this->render('admin/users', [
            'title' => 'Пользователи',
            'page_title' => 'Пользователи',
            'users' => $users,
        ]);
    }

    public function toggleActive(Request $request): void
    {
        $id = (int)$request->params['user_id'];
        $user = User::findById($id);
        if ($user) {
            User::update($id, ['is_active' => $user['is_active'] ? 0 : 1]);
        }
        set_flash('success', 'Статус пользователя изменён.');
        $this->redirect(url('admin/users'));
    }

    public function toggleAdmin(Request $request): void
    {
        $id = (int)$request->params['user_id'];
        $user = User::findById($id);
        if ($user) {
            $newRole = $user['role'] === 'admin' ? 'user' : 'admin';
            User::update($id, ['role' => $newRole]);
        }
        set_flash('success', 'Роль пользователя изменена.');
        $this->redirect(url('admin/users'));
    }

    public function videos(Request $request): void
    {
        $videos = Video::allForAdmin();
        echo $this->render('admin/videos', [
            'title' => 'Видео',
            'page_title' => 'Видео',
            'videos' => $videos,
        ]);
    }

    public function publishVideo(Request $request): void
    {
        $id = (int)$request->params['video_id'];
        Video::update($id, ['status' => Video::STATUS_PUBLISHED, 'published_at' => date('Y-m-d H:i:s')]);
        set_flash('success', 'Видео опубликовано.');
        $this->redirect(url('admin/videos'));
    }

    public function blockVideo(Request $request): void
    {
        $id = (int)$request->params['video_id'];
        Video::update($id, ['status' => Video::STATUS_BLOCKED]);
        set_flash('success', 'Видео заблокировано.');
        $this->redirect(url('admin/videos'));
    }

    public function deleteVideo(Request $request): void
    {
        $id = (int)$request->params['video_id'];
        $video = Video::findById($id);
        if ($video) {
            StorageService::delete($video['storage_path']);
            Video::delete($id);
        }
        set_flash('success', 'Видео удалено.');
        $this->redirect(url('admin/videos'));
    }

    public function comments(Request $request): void
    {
        $comments = Comment::allForAdmin();
        echo $this->render('admin/comments', [
            'title' => 'Комментарии',
            'page_title' => 'Комментарии',
            'comments' => $comments,
        ]);
    }

    public function hideComment(Request $request): void
    {
        Comment::updateStatus((int)$request->params['comment_id'], Comment::STATUS_HIDDEN);
        set_flash('success', 'Комментарий скрыт.');
        $this->redirect(url('admin/comments'));
    }

    public function restoreComment(Request $request): void
    {
        Comment::updateStatus((int)$request->params['comment_id'], Comment::STATUS_VISIBLE);
        set_flash('success', 'Комментарий восстановлен.');
        $this->redirect(url('admin/comments'));
    }

    public function deleteComment(Request $request): void
    {
        $id = (int)$request->params['comment_id'];
        $comment = Comment::findById($id);
        Comment::delete($id);
        if ($comment) {
            Video::update($comment['video_id'], ['comment_count' => Comment::countByVideo($comment['video_id'])]);
        }
        set_flash('success', 'Комментарий удалён.');
        $this->redirect(url('admin/comments'));
    }

    public function reports(Request $request): void
    {
        $reports = Report::allOpen();
        echo $this->render('admin/reports', [
            'title' => 'Жалобы',
            'page_title' => 'Жалобы',
            'reports' => $reports,
        ]);
    }

    public function resolveReport(Request $request): void
    {
        Report::resolve((int)$request->params['report_id']);
        set_flash('success', 'Жалоба закрыта.');
        $this->redirect(url('admin/reports'));
    }

    public function dismissReport(Request $request): void
    {
        Report::dismiss((int)$request->params['report_id']);
        set_flash('success', 'Жалоба отклонена.');
        $this->redirect(url('admin/reports'));
    }

    public function aiLogs(Request $request): void
    {
        $logs = AiRequest::recent(100);
        echo $this->render('admin/ai', [
            'title' => 'ИИ-логи',
            'page_title' => 'ИИ-запросы',
            'logs' => $logs,
        ]);
    }

    public function settings(Request $request): void
    {
        $keys = ['enable_ai', 'require_video_approval', 'video_max_mb', 'ai_daily_limit_auth', 'registration_open'];
        $values = [];
        foreach ($keys as $k) {
            $values[$k] = Setting::get($k, (string)Env::get(strtoupper($k), ''));
        }
        echo $this->render('admin/settings', [
            'title' => 'Настройки',
            'page_title' => 'Настройки',
            'values' => $values,
        ]);
    }

    public function updateSettings(Request $request): void
    {
        $values = [
            'enable_ai'             => $request->post('enable_ai') === '1' ? '1' : '0',
            'require_video_approval' => $request->post('require_video_approval') === '1' ? '1' : '0',
            'video_max_mb'          => (string)max(1, (int)$request->post('video_max_mb', 1024)),
            'ai_daily_limit_auth'   => (string)max(0, (int)$request->post('ai_daily_limit_auth', 100)),
            'registration_open'     => $request->post('registration_open') === '1' ? '1' : '0',
        ];
        foreach ($values as $k => $v) {
            Setting::set($k, $v);
        }
        set_flash('success', 'Настройки сохранены.');
        $this->redirect(url('admin/settings'));
    }
}
