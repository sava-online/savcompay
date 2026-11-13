<?php
/** @var array $values */
?>
<div class="breadcrumbs"><a href="<?= url('admin') ?>">Админ</a> / Настройки</div>
<h1 class="section-title">Настройки</h1>
<div class="glass-strong" style="max-width:640px;padding:22px">
    <form action="<?= url('admin/settings') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field checkbox-row">
            <input type="checkbox" name="enable_ai" value="1" id="ai" <?= !empty($values['enable_ai']) ? 'checked' : '' ?>>
            <label for="ai" style="color:var(--text)">Включить ИИ-функции</label>
        </div>
        <div class="field checkbox-row">
            <input type="checkbox" name="require_video_approval" value="1" id="appr" <?= !empty($values['require_video_approval']) ? 'checked' : '' ?>>
            <label for="appr" style="color:var(--text)">Требовать премодерацию видео админом</label>
        </div>
        <div class="field checkbox-row">
            <input type="checkbox" name="registration_open" value="1" id="reg" <?= !empty($values['registration_open']) ? 'checked' : '' ?>>
            <label for="reg" style="color:var(--text)">Открыть регистрацию новых пользователей</label>
        </div>
        <div class="field">
            <label>Максимальный размер загрузки (МБ)</label>
            <input class="input" type="number" name="video_max_mb" min="1" value="<?= e($values['video_max_mb'] ?? 1024) ?>">
        </div>
        <div class="field">
            <label>Дневной лимит ИИ-запросов (авторизованные)</label>
            <input class="input" type="number" name="ai_daily_limit_auth" min="0" value="<?= e($values['ai_daily_limit_auth'] ?? 100) ?>">
        </div>
        <button class="btn btn-primary" type="submit">Сохранить</button>
    </form>
</div>
