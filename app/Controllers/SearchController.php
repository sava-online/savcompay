<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Env;
use App\Models\Video;
use App\Models\Category;
use App\Services\AiService;

class SearchController extends BaseController
{
    public function index(Request $request): void
    {
        $q = trim((string)$request->get('q', ''));
        $categoryId = $request->get('category', '');
        $sort = $request->get('sort', 'relevance');
        $useAi = $request->get('ai') === '1' && Env::bool('ENABLE_AI', true);
        $explanation = '';

        $categories = Category::all();
        $results = [];
        $total = 0;

        if ($q !== '') {
            $query = $q;
            if ($useAi) {
                $ai = new AiService(is_logged_in() ? (int)$this->user()['id'] : null);
                $res = $ai->smartSearch($q);
                if (!empty($res['query'])) {
                    $query = $res['query'];
                }
                if (!empty($res['explanation'])) {
                    $explanation = $res['explanation'];
                }
            }

            $perPage = 24;
            $page = max(1, (int)$request->get('page', 1));
            $offset = ($page - 1) * $perPage;
            $opts = [
                'category_id' => $categoryId !== '' ? (int)$categoryId : null,
                'limit'       => $perPage,
                'offset'      => $offset,
            ];
            $results = Video::search($query, $opts);
            $total = Video::searchCount($query, $opts);
        }

        echo $this->render('search', [
            'title'      => $q !== '' ? 'Поиск: ' . e($q) : 'Поиск',
            'page_title' => 'Поиск',
            'q'          => $q,
            'category'   => $categoryId,
            'sort'       => $sort,
            'use_ai'     => $useAi,
            'explanation'=> $explanation,
            'categories' => $categories,
            'results'    => $results,
            'total'      => $total,
        ]);
    }
}
