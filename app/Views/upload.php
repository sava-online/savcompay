<?php
/** @var array $categories */
$allowed = config('ALLOWED_VIDEO_EXTENSIONS', 'mp4,webm');
$maxMb = config('VIDEO_MAX_MB', 1024);
?>
<script>window.GT_ALLOWED_EXT = "<?= e($allowed) ?>"; window.GT_MAX_MB = "<?= e($maxMb) ?>";</script>

<div class="glass-strong" style="max-width:720px;margin:0 auto;padding:24px">
    <h1 style="margin-top:0">Загрузка видео</h1>
    <p class="muted">Поддерживаются форматы <?= e($allowed) ?>, до <?= e($maxMb) ?> МБ.</p>

    <div class="ai-panel quiet-hide">
        <h4>✨ ИИ-помощник</h4>
        <p class="muted text-sm">Пусть ИИ улучшит название, описание и подберёт теги.</p>
        <div class="ai-actions">
            <button type="button" class="btn btn-sm" data-ai="generate-metadata">Сгенерировать метаданные</button>
            <button type="button" class="btn btn-sm" data-ai="suggest-tags">Предложить теги</button>
        </div>
        <div id="ai-meta-result"></div>
    </div>

    <form id="upload-form" action="<?= url('upload') ?>" method="post" enctype="multipart/form-data" class="mt-2">
        <?= csrf_field() ?>
        <div class="field">
            <label>Видеофайл</label>
            <input class="input" type="file" id="video_file" name="video_file" accept="video/mp4,video/webm" required>
        </div>
        <div class="field">
            <label>Название</label>
            <input class="input" type="text" id="title" name="title" maxlength="160" value="<?= e(old('title')) ?>" required>
        </div>
        <div class="field">
            <label>Описание</label>
            <textarea class="input" id="description" name="description" maxlength="4000"><?= e(old('description')) ?></textarea>
        </div>
        <div class="field">
            <label>Категория</label>
            <select class="input" id="category_id" name="category_id">
                <option value="">— без категории —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= (old('category_id') == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Теги (через запятую)</label>
            <input class="input" type="text" id="tags" name="tags" value="<?= e(old('tags')) ?>" placeholder="дизайн, музыка, обучение">
        </div>
        <div class="field">
            <label>Видимость</label>
            <select class="input" name="visibility">
                <option value="public" selected>Публичное</option>
                <option value="unlisted">Ссылка (unlisted)</option>
                <option value="private">Приватное</option>
            </select>
        </div>
        <div class="field checkbox-row">
            <input type="checkbox" name="accept_rights" value="1" id="rights" required>
            <label for="rights" style="color:var(--text)">Я обладаю правами на это видео или имею разрешение на его публикацию.</label>
        </div>
        <div id="upload-error" class="muted" style="color:var(--danger)"></div>
        <div id="upload-progress-wrap" style="display:none;margin:10px 0">
            <div style="height:8px;background:var(--glass-bg);border-radius:999px;overflow:hidden">
                <div id="upload-progress" style="height:100%;width:0;background:linear-gradient(90deg,var(--accent),var(--accent-2))"></div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%">Загрузить видео</button>
    </form>
</div>
