<?php
/** @var array $video */
/** @var array $categories */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / <a href="<?= url('watch/' . $video['id']) ?>"><?= e($video['title']) ?></a> / Редактирование</div>
<div class="glass-strong" style="max-width:720px;padding:22px">
    <h1 style="margin-top:0">Редактирование видео</h1>
    <form action="<?= url('video/' . $video['id'] . '/edit') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Название</label>
            <input class="input" name="title" maxlength="160" value="<?= e($video['title']) ?>" required>
        </div>
        <div class="field">
            <label>Описание</label>
            <textarea class="input" name="description" maxlength="4000"><?= e($video['description'] ?? '') ?></textarea>
        </div>
        <div class="field">
            <label>Категория</label>
            <select class="input" name="category_id">
                <option value="">— без категории —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= ($video['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Видимость</label>
            <select class="input" name="visibility">
                <option value="public" <?= $video['visibility'] === 'public' ? 'selected' : '' ?>>Публичное</option>
                <option value="unlisted" <?= $video['visibility'] === 'unlisted' ? 'selected' : '' ?>>Ссылка (unlisted)</option>
                <option value="private" <?= $video['visibility'] === 'private' ? 'selected' : '' ?>>Приватное</option>
            </select>
        </div>
        <div class="row">
            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a class="btn" href="<?= url('watch/' . $video['id']) ?>">Отмена</a>
        </div>
    </form>
    <hr class="sep">
    <form action="<?= url('video/' . $video['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Удалить видео безвозвратно?');">
        <?= csrf_field() ?>
        <button class="btn btn-danger btn-sm" type="submit">Удалить видео</button>
    </form>
</div>
