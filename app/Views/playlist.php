<?php
/** @var array $playlist */
/** @var array $items */
/** @var bool $is_owner */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / Плейлисты / <?= e($playlist['name']) ?></div>
<div class="channel-head glass-strong">
    <div style="flex:1">
        <div class="name"><?= e($playlist['name']) ?></div>
        <div class="desc"><?= e($playlist['description'] ?: 'Плейлист GlassTube') ?></div>
        <div class="muted text-sm"><?= e(count($items)) ?> видео · <?= e($playlist['visibility']) ?></div>
    </div>
    <?php if ($is_owner): ?>
        <a class="btn btn-sm" href="<?= url('playlist/' . $playlist['id'] . '/edit') ?>">Редактировать</a>
    <?php endif; ?>
</div>

<?php if (empty($items)): ?>
    <div class="empty">В плейлисте пока нет видео.</div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($items as $v): ?>
            <div class="video-card">
                <a href="<?= url('watch/' . $v['id']) ?>">
                    <div class="thumb">
                        <?php if (!empty($v['thumbnail_path'])): ?><img src="/stream/thumbnail/<?= rawurlencode(basename($v['thumbnail_path'])) ?>" alt=""><?php endif; ?>
                    </div>
                    <div class="meta">
                        <div class="title"><?= e($v['title']) ?></div>
                        <div class="sub"><?= e($v['channel_name']) ?></div>
                    </div>
                </a>
                <?php if ($is_owner): ?>
                    <div class="meta">
                        <button class="btn btn-sm btn-ghost" data-action="playlist-remove" data-playlist="<?= e($playlist['id']) ?>" data-video="<?= e($v['id']) ?>">Убрать</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($is_owner): ?>
<script type="module">
import { postJson } from '/assets/js/app.js';
document.querySelectorAll('[data-action="playlist-remove"]').forEach(b => {
    b.addEventListener('click', async () => {
        const { ok } = await postJson('/playlist/' + b.dataset.playlist + '/remove', { video_id: b.dataset.video });
        if (ok) location.reload();
    });
});
</script>
<?php endif; ?>
