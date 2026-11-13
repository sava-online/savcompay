<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Subscription;
use App\Services\StreamingService;

class ChannelController extends BaseController
{
    public function show(Request $request): void
    {
        $slug = $request->params['channel_slug'];
        $channel = Channel::findBySlug($slug);
        if (!$channel) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        $user = $this->user();
        $isOwner = $user && (int)$user['id'] === (int)$channel['user_id'];

        if ($isOwner || is_admin()) {
            $videos = Video::allByChannel((int)$channel['id']);
        } else {
            $videos = Video::list(['channel_id' => $channel['id'], 'visibility' => 'public', 'limit' => 48]);
        }

        $subscribed = false;
        $subsCount = Subscription::countForChannel((int)$channel['id']);
        if ($user) {
            $subscribed = Subscription::exists((int)$user['id'], (int)$channel['id']);
        }

        echo $this->render('channel', [
            'title'      => e($channel['name']),
            'page_title' => e($channel['name']),
            'channel'    => $channel,
            'videos'     => $videos,
            'is_owner'   => $isOwner,
            'subscribed' => $subscribed,
            'subs_count' => $subsCount,
        ]);
    }

    public function edit(Request $request): void
    {
        $slug = $request->params['channel_slug'];
        $channel = Channel::findBySlug($slug);
        if (!$channel || !$this->isOwner($channel)) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        echo $this->render('channel_edit', [
            'title'   => 'Редактирование канала',
            'channel' => $channel,
        ]);
    }

    public function update(Request $request): void
    {
        $slug = $request->params['channel_slug'];
        $channel = Channel::findBySlug($slug);
        if (!$channel || !$this->isOwner($channel)) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        $name = trim((string)$request->post('name', ''));
        $description = trim((string)$request->post('description', ''));
        if ($name === '') {
            set_flash('error', 'Укажите название канала.');
            $this->redirect(url('channel/' . $slug . '/edit'));
        }
        Channel::update($channel['id'], [
            'name'        => $name,
            'description' => $description === '' ? null : $description,
        ]);
        set_flash('success', 'Канал обновлён.');
        $this->redirect(url('channel/' . $slug));
    }

    public function subscribe(Request $request): void
    {
        $user = $this->user();
        if (!$user) {
            $this->json(['error' => 'auth_required'], 401);
        }
        $channel = Channel::findById((int)$request->params['channel_id']);
        if (!$channel) {
            $this->json(['error' => 'not_found'], 404);
        }
        if ((int)$user['id'] === (int)$channel['user_id']) {
            $this->json(['error' => 'cannot_subscribe_self'], 400);
        }
        $subscribed = Subscription::toggle((int)$user['id'], (int)$channel['id']);
        $this->json([
            'subscribed'  => $subscribed,
            'subscribers' => Subscription::countForChannel((int)$channel['id']),
        ]);
    }

    private function isOwner(array $channel): bool
    {
        $user = $this->user();
        return $user && (int)$user['id'] === (int)$channel['user_id'];
    }
}
