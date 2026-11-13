<?php
/** @var array $playlist */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / <a href="<?= url('playlist/' . $playlist['id']) ?>"><?= e($playlist['name']) ?></a> / Редактирование</div>
<div class="glass-strong" style="max-width:640px;padding:22px">
    <h1 style="margin-top:0">Редактирование плейлиста</h1>
    <form action="<?= url('playlist/' . $playlist['id'] . '/edit') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Название</label>
            <input class="input" name="name" maxlength="100" value="<?= e($playlist['name']) ?>" required>
        </div>
        <div class="field">
            <label>Описание</label>
            <textarea class="input" name="description" maxlength="1000"><?= e($playlist['description'] ?? '') ?></textarea>
        </div>
        <div class="field">
            <label>Видимость</label>
            <select class="input" name="visibility">
                <option value="private" <?= $playlist['visibility'] === 'private' ? 'selected' : '' ?>>Приватный</option>
                <option value="public" <?= $playlist['visibility'] === 'public' ? 'selected' : '' ?>>Публичный</option>
            </select>
        </div>
        <div class="row">
            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a class="btn" href="<?= url('playlist/' . $playlist['id']) ?>">Отмена</a>
        </div>
    </form>
</div>
