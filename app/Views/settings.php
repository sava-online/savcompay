<?php
/** @var array $channel */
$user = auth_user();
$playlists = \App\Models\Playlist::findByUser((int)$user['id']);
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / Настройки</div>
<h1 class="section-title">Настройки профиля</h1>

<div class="glass-strong" style="padding:22px;max-width:720px">
    <form action="<?= url('settings') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Логин</label>
            <input class="input" value="<?= e($user['username']) ?>" disabled>
        </div>
        <div class="field">
            <label>Email</label>
            <input class="input" value="<?= e($user['email']) ?>" disabled>
        </div>
        <div class="field">
            <label>О себе</label>
            <textarea class="input" name="bio" maxlength="500"><?= e(old('bio') ?: ($user['bio'] ?? '')) ?></textarea>
        </div>
        <hr class="sep">
        <h3 style="margin:0 0 8px">Канал</h3>
        <div class="field">
            <label>Название канала</label>
            <input class="input" name="channel_name" maxlength="80" value="<?= e(old('channel_name') ?: $channel['name']) ?>" required>
        </div>
        <div class="field">
            <label>Описание канала</label>
            <textarea class="input" name="channel_description" maxlength="1000"><?= e(old('channel_description') ?: ($channel['description'] ?? '')) ?></textarea>
        </div>
        <hr class="sep">
        <h3 style="margin:0 0 8px">Смена пароля</h3>
        <div class="field">
            <label>Текущий пароль</label>
            <input class="input" type="password" name="current_password">
        </div>
        <div class="field">
            <label>Новый пароль (минимум 8 символов)</label>
            <input class="input" type="password" name="new_password" minlength="8">
        </div>
        <button class="btn btn-primary" type="submit">Сохранить</button>
    </form>
</div>

<div class="glass-strong mt-2" style="padding:22px;max-width:720px">
    <h3 style="margin:0 0 8px">Плейлисты</h3>
    <form class="row" action="<?= url('playlist/create') ?>" method="post">
        <?= csrf_field() ?>
        <input class="input" name="name" placeholder="Название плейлиста" required style="flex:1">
        <select class="input" name="visibility" style="width:auto">
            <option value="private">Приватный</option>
            <option value="public">Публичный</option>
        </select>
        <button class="btn btn-primary" type="submit">Создать</button>
    </form>
    <?php if (empty($playlists)): ?>
        <p class="muted mt-1">Плейлистов пока нет.</p>
    <?php else: ?>
        <ul class="mt-1" style="list-style:none;padding:0;margin:8px 0 0">
            <?php foreach ($playlists as $p): ?>
                <li class="row" style="padding:8px 0;border-top:1px solid var(--glass-border)">
                    <a href="<?= url('playlist/' . $p['id']) ?>"><?= e($p['name']) ?></a>
                    <span class="spacer"></span>
                    <span class="muted text-sm"><?= e($p['item_count']) ?> видео · <?= e($p['visibility']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
