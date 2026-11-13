<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Tag;
use App\Models\Video;

class TagController extends BaseController
{
    public function show(Request $request): void
    {
        $slug = $request->params['tag_slug'];
        $tag = Tag::findBySlug($slug);
        if (!$tag) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        $videos = Video::list([
            'tag_id'    => $tag['id'],
            'visibility' => 'public',
            'sort'      => 'new',
            'limit'     => 36,
        ]);
        echo $this->render('tag', [
            'title'      => '#' . e($tag['name']),
            'page_title' => '#' . e($tag['name']),
            'tag'        => $tag,
            'videos'     => $videos,
        ]);
    }
}
