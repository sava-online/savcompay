<?php
/** @var array $channel */
/** @var array $videos */
/** @var bool $is_owner */
/** @var bool $subscribed */
/** @var int $subs_count */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / <?= e($channel['name']) ?></div>

<div class="channel-head glass-strong">
    <img class="avatar lg" src="/stream/avatar/<?= rawurlencode($channel['name']) ?>" alt="">
    <div style="flex:1">
        <div class="name"><?= e($channel['name']) ?></div>
        <div class="desc"><?= e($channel['description'] ?: 'Канал в GlassTube') ?></div>
        <div class="muted text-sm"><?= e($subs_count) ?> подписчиков · <?= e(count($videos)) ?> видео</div>
    </div>
    <div class="row">
        <?php if ($is_owner): ?>
            <a class="btn btn-sm" href="<?= url('channel/' . $channel['slug'] . '/edit') ?>">Редактировать</a>
        <?php elseif (is_logged_in()): ?>
            <button class="btn btn-sm" data-action="subscribe" data-id="<?= e($channel['id']) ?>">
                <?= $subscribed ? '✓ Вы подписаны' : '＋ Подписаться' ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($videos)): ?>
    <div class="empty">На канале пока нет видео.</div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($videos as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
    </div>
<?php endif; ?>
