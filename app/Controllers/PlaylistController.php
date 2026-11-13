<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Core\Session;
use App\Models\Playlist;
use App\Models\Video;

class PlaylistController extends BaseController
{
    public function show(Request $request): void
    {
        $id = (int)$request->params['playlist_id'];
        $user = $this->user();
        $userId = $user ? (int)$user['id'] : null;
        $playlist = Playlist::findVisible($id, $userId);
        if (!$playlist) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        $isOwner = $userId !== null && (int)$userId === (int)$playlist['user_id'];
        $items = Playlist::items($id);
        echo $this->render('playlist', [
            'title'    => e($playlist['name']),
            'page_title' => e($playlist['name']),
            'playlist' => $playlist,
            'items'    => $items,
            'is_owner' => $isOwner,
        ]);
    }

    public function create(Request $request): void
    {
        $user = $this->user();
        $name = trim((string)$request->post('name', ''));
        $visibility = $request->post('visibility', 'private');
        $description = trim((string)$request->post('description', ''));

        $validator = new Validator();
        $errors = $validator->validate(['name' => $name], ['name' => 'required|max:100']);
        if (!in_array($visibility, ['private', 'public'], true)) {
            $errors['visibility'] = 'Недопустимая видимость.';
        }
        if (!empty($errors)) {
            set_flash('error', 'Укажите название плейлиста.');
            $this->redirect(url('settings'));
        }
        $id = Playlist::createPlaylist((int)$user['id'], $name, $visibility, $description === '' ? null : $description);
        set_flash('success', 'Плейлист создан.');
        $this->redirect(url('playlist/' . $id));
    }

    public function add(Request $request): void
    {
        $user = $this->user();
        if (!$user) {
            $this->json(['error' => 'auth_required'], 401);
        }
        $playlist = Playlist::findById((int)$request->params['playlist_id']);
        if (!$playlist || (int)$playlist['user_id'] !== (int)$user['id']) {
            $this->json(['error' => 'forbidden'], 403);
        }
        $videoId = (int)$request->post('video_id', 0);
        if (!Video::findById($videoId)) {
            $this->json(['error' => 'not_found'], 404);
        }
        Playlist::addItem((int)$playlist['id'], $videoId);
        $this->json(['ok' => true]);
    }

    public function remove(Request $request): void
    {
        $user = $this->user();
        if (!$user) {
            $this->json(['error' => 'auth_required'], 401);
        }
        $playlist = Playlist::findById((int)$request->params['playlist_id']);
        if (!$playlist || (int)$playlist['user_id'] !== (int)$user['id']) {
            $this->json(['error' => 'forbidden'], 403);
        }
        $videoId = (int)$request->post('video_id', 0);
        Playlist::removeItem((int)$playlist['id'], $videoId);
        $this->json(['ok' => true]);
    }

    public function edit(Request $request): void
    {
        $user = $this->user();
        $playlist = Playlist::findById((int)$request->params['playlist_id']);
        if (!$playlist || (int)$playlist['user_id'] !== (int)$user['id']) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        echo $this->render('playlist_edit', [
            'title'    => 'Редактирование плейлиста',
            'playlist' => $playlist,
        ]);
    }

    public function update(Request $request): void
    {
        $user = $this->user();
        $playlist = Playlist::findById((int)$request->params['playlist_id']);
        if (!$playlist || (int)$playlist['user_id'] !== (int)$user['id']) {
            (new ErrorController($this->app))->notFound($request);
            return;
        }
        $name = trim((string)$request->post('name', ''));
        $visibility = $request->post('visibility', 'private');
        $description = trim((string)$request->post('description', ''));
        if ($name === '') {
            set_flash('error', 'Название обязательно.');
            $this->redirect(url('playlist/' . $playlist['id'] . '/edit'));
        }
        Playlist::update((int)$playlist['id'], [
            'name'        => $name,
            'visibility'  => in_array($visibility, ['private', 'public'], true) ? $visibility : 'private',
            'description' => $description === '' ? null : $description,
        ]);
        set_flash('success', 'Плейлист обновлён.');
        $this->redirect(url('playlist/' . $playlist['id']));
    }
}
