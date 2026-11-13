<?php
/** @var array $video */
$thumb = !empty($video['thumbnail_path'])
    ? '/stream/thumbnail/' . rawurlencode(basename($video['thumbnail_path']))
    : null;
$slug = $video['slug'] ?? $video['id'];
?>
<a class="video-card" href="<?= url('watch/' . $video['id']) ?>">
    <div class="thumb">
        <?php if ($thumb): ?><img src="<?= e($thumb) ?>" alt="" loading="lazy"><?php endif; ?>
        <?php if (!empty($video['duration_seconds'])): ?>
            <span class="duration"><?= e(format_duration((int)$video['duration_seconds'])) ?></span>
        <?php endif; ?>
    </div>
    <div class="meta">
        <div class="title"><?= e($video['title']) ?></div>
        <div class="sub"><?= e($video['channel_name'] ?? '') ?> · <?= e(format_number((int)($video['view_count'] ?? 0))) ?> просм.</div>
        <?php if (!empty($video['category_name'])): ?>
            <div class="sub"><span class="badge"><?= e($video['category_name']) ?></span></div>
        <?php endif; ?>
    </div>
</a>
