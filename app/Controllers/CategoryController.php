<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Category;
use App\Models\Video;

class CategoryController extends BaseController
{
    public function show(Request $request): void
    {
        $slug = $request->params['category_slug'];
        $category = Category::findBySlug($slug);
        if (!$category) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        $sort = $request->get('sort', 'new');
        $videos = Video::list([
            'category_id' => $category['id'],
            'visibility'  => 'public',
            'sort'        => $sort,
            'limit'       => 36,
        ]);
        $allCategories = Category::all();
        echo $this->render('category', [
            'title'        => e($category['name']),
            'page_title'   => e($category['name']),
            'category'     => $category,
            'videos'       => $videos,
            'sort'         => $sort,
            'categories'   => $allCategories,
        ]);
    }
}
