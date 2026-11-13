<?php
/** @var array $tag */
/** @var array $videos */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / Теги / #<?= e($tag['name']) ?></div>
<h1 class="section-title">#<?= e($tag['name']) ?></h1>

<?php if (empty($videos)): ?>
    <div class="empty">Нет видео с этим тегом.</div>
<?php else: ?>
    <div class="grid mt-1">
        <?php foreach ($videos as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
    </div>
<?php endif; ?>
