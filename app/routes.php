<?php
/**
 * Route registration. Called from public/index.php with the Application instance.
 */

use App\Core\Application;

function glasstube_routes(Application $app): void
{
    $r = $app->router;
    $auth = [\App\Middleware\AuthMiddleware::class];
    $admin = [\App\Middleware\AdminMiddleware::class];

    // ---------------- Public pages ----------------
    $r->get('/', 'HomeController@index');
    $r->get('/login', 'AuthController@showLogin');
    $r->post('/login', 'AuthController@login');
    $r->get('/register', 'AuthController@showRegister');
    $r->post('/register', 'AuthController@register');
    $r->post('/logout', 'AuthController@logout');
    $r->get('/rules', 'HomeController@rules');

    $r->get('/watch/{video_id}', 'VideoController@watch');
    $r->get('/video/{video_id}/stream', 'StreamController@stream');
    $r->get('/channel/{channel_slug}', 'ChannelController@show');
    $r->get('/category/{category_slug}', 'CategoryController@show');
    $r->get('/tag/{tag_slug}', 'TagController@show');
    $r->get('/search', 'SearchController@index');
    $r->get('/playlists/{playlist_id}', 'PlaylistController@show');

    $r->get('/stream/avatar/{name}', 'StreamController@avatar');
    $r->get('/stream/thumbnail/{name}', 'StreamController@thumbnail');

    // ---------------- Authenticated user pages ----------------
    $r->get('/upload', 'UploadController@show', $auth);
    $r->post('/upload', 'UploadController@store', $auth);
    $r->get('/settings', 'AuthController@settings', $auth);
    $r->post('/settings', 'AuthController@updateSettings', $auth);
    $r->get('/subscriptions', 'HomeController@subscriptions', $auth);
    $r->get('/history', 'HomeController@history', $auth);

    $r->get('/video/{video_id}/edit', 'VideoController@edit', $auth);
    $r->post('/video/{video_id}/edit', 'VideoController@update', $auth);
    $r->post('/video/{video_id}/delete', 'VideoController@delete', $auth);

    $r->get('/channel/{channel_slug}/edit', 'ChannelController@edit', $auth);
    $r->post('/channel/{channel_slug}/edit', 'ChannelController@update', $auth);

    $r->get('/playlist/{playlist_id}/edit', 'PlaylistController@edit', $auth);
    $r->post('/playlist/{playlist_id}/edit', 'PlaylistController@update', $auth);
    $r->post('/playlist/create', 'PlaylistController@create', $auth);
    $r->post('/playlist/{playlist_id}/add', 'PlaylistController@add', $auth);
    $r->post('/playlist/{playlist_id}/remove', 'PlaylistController@remove', $auth);

    // ---------------- API ----------------
    $r->post('/api/comments/{video_id}', 'CommentController@add', $auth);
    $r->post('/api/comments/{comment_id}/delete', 'CommentController@delete', $auth);
    $r->post('/api/videos/{video_id}/like', 'VideoController@like', $auth);
    $r->post('/api/videos/{video_id}/save', 'VideoController@save', $auth);
    $r->post('/api/videos/{video_id}/progress', 'VideoController@progress', $auth);
    $r->post('/api/reports', 'ReportController@store', $auth);
    $r->post('/api/channels/{channel_id}/subscribe', 'ChannelController@subscribe', $auth);

    $r->post('/api/ai/generate-metadata', 'AiApiController@generateMetadata');
    $r->post('/api/ai/moderate-text', 'AiApiController@moderate');
    $r->post('/api/ai/summarize-video', 'AiApiController@summarize');
    $r->post('/api/ai/suggest-tags', 'AiApiController@suggestTags');
    $r->post('/api/ai/search', 'AiApiController@search');
    $r->get('/api/ai/limits', 'AiApiController@limits');

    // ---------------- Admin ----------------
    $r->get('/admin', 'AdminController@dashboard', $admin);
    $r->get('/admin/users', 'AdminController@users', $admin);
    $r->post('/admin/users/{user_id}/toggle-active', 'AdminController@toggleActive', $admin);
    $r->post('/admin/users/{user_id}/toggle-admin', 'AdminController@toggleAdmin', $admin);
    $r->get('/admin/videos', 'AdminController@videos', $admin);
    $r->post('/admin/videos/{video_id}/publish', 'AdminController@publishVideo', $admin);
    $r->post('/admin/videos/{video_id}/block', 'AdminController@blockVideo', $admin);
    $r->post('/admin/videos/{video_id}/delete', 'AdminController@deleteVideo', $admin);
    $r->get('/admin/comments', 'AdminController@comments', $admin);
    $r->post('/admin/comments/{comment_id}/hide', 'AdminController@hideComment', $admin);
    $r->post('/admin/comments/{comment_id}/restore', 'AdminController@restoreComment', $admin);
    $r->post('/admin/comments/{comment_id}/delete', 'AdminController@deleteComment', $admin);
    $r->get('/admin/reports', 'AdminController@reports', $admin);
    $r->post('/admin/reports/{report_id}/resolve', 'AdminController@resolveReport', $admin);
    $r->post('/admin/reports/{report_id}/dismiss', 'AdminController@dismissReport', $admin);
    $r->get('/admin/ai', 'AdminController@aiLogs', $admin);
    $r->get('/admin/settings', 'AdminController@settings', $admin);
    $r->post('/admin/settings', 'AdminController@updateSettings', $admin);
}
