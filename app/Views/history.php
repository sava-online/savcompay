<?php
/** @var array $videos */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / История</div>
<h1 class="section-title">История просмотра</h1>
<?php if (empty($videos)): ?>
    <div class="empty">История пуста. Начните смотреть видео — прогресс сохранится здесь.</div>
<?php else: ?>
    <div class="grid mt-1">
        <?php foreach ($videos as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
    </div>
<?php endif; ?>
