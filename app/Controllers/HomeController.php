<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Video;
use App\Models\Category;
use App\Models\WatchHistory;

class HomeController extends BaseController
{
    public function index(Request $request): void
    {
        $new = Video::list(['visibility' => 'public', 'sort' => 'new', 'limit' => 12]);
        $popular = Video::list(['visibility' => 'public', 'sort' => 'popular', 'limit' => 8]);
        $categories = Category::all();

        $continue = [];
        if (is_logged_in()) {
            $continue = Video::continueWatching((int)$this->user()['id'], 8);
        }

        // Pagination for "load more" on new videos.
        $page = max(1, (int)$request->get('page', 1));
        $offset = ($page - 1) * 12;
        $moreNew = Video::list(['visibility' => 'public', 'sort' => 'new', 'limit' => 12, 'offset' => $offset]);
        $hasMore = count($moreNew) === 12;

        echo $this->render('home', [
            'title'      => 'GlassTube — спокойный видеохостинг',
            'page_title' => 'GlassTube',
            'new'        => $page > 1 ? $moreNew : $new,
            'popular'    => $popular,
            'categories' => $categories,
            'continue'   => $continue,
            'page'       => $page,
            'has_more'   => $hasMore,
        ]);
    }

    public function rules(Request $request): void
    {
        echo $this->render('rules', [
            'title'      => 'Правила сообщества',
            'page_title' => 'Правила',
        ]);
    }

    public function subscriptions(Request $request): void
    {
        $videos = Video::forSubscriptions((int)$this->user()['id'], 36);
        echo $this->render('subscriptions', [
            'title'      => 'Подписки',
            'page_title' => 'Подписки',
            'videos'     => $videos,
        ]);
    }

    public function history(Request $request): void
    {
        $videos = Video::continueWatching((int)$this->user()['id'], 24);
        echo $this->render('history', [
            'title'      => 'История просмотра',
            'page_title' => 'История',
            'videos'     => $videos,
        ]);
    }
}
