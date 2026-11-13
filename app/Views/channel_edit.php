<?php
/** @var array $channel */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / <a href="<?= url('channel/' . $channel['slug']) ?>"><?= e($channel['name']) ?></a> / Редактирование</div>
<div class="glass-strong" style="max-width:640px;padding:22px">
    <h1 style="margin-top:0">Редактирование канала</h1>
    <form action="<?= url('channel/' . $channel['slug'] . '/edit') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Название канала</label>
            <input class="input" name="name" maxlength="80" value="<?= e($channel['name']) ?>" required>
        </div>
        <div class="field">
            <label>Описание</label>
            <textarea class="input" name="description" maxlength="1000"><?= e($channel['description'] ?? '') ?></textarea>
        </div>
        <div class="row">
            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a class="btn" href="<?= url('channel/' . $channel['slug']) ?>">Отмена</a>
        </div>
    </form>
</div>
