<?php
/** @var array $videos */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / Подписки</div>
<h1 class="section-title">Подписки</h1>
<?php if (empty($videos)): ?>
    <div class="empty">Вы ещё не подписаны ни на один канал или там пока нет новых видео.
        <br><a href="<?= url('') ?>">Откройте главную</a>, чтобы находить каналы.</div>
<?php else: ?>
    <div class="grid mt-1">
        <?php foreach ($videos as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
    </div>
<?php endif; ?>
