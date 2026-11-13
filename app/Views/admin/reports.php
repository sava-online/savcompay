<?php
/** @var array $reports */
?>
<div class="breadcrumbs"><a href="<?= url('admin') ?>">Админ</a> / Жалобы</div>
<h1 class="section-title">Жалобы</h1>
<?php if (empty($reports)): ?>
    <div class="empty">Открытых жалоб нет.</div>
<?php else: ?>
<div class="glass-strong" style="padding:8px;overflow-x:auto">
<table class="table">
    <thead><tr><th>ID</th><th>Причина</th><th>Объект</th><th>Детали</th><th>Отправитель</th><th>Действия</th></tr></thead>
    <tbody>
    <?php foreach ($reports as $r): ?>
        <tr>
            <td><?= e($r['id']) ?></td>
            <td><?= e($r['reason']) ?></td>
            <td>
                <?php if ($r['video_id']): ?><a href="<?= url('watch/' . $r['video_id']) ?>">Видео #<?= e($r['video_id']) ?></a><?php endif; ?>
                <?php if ($r['comment_id']): ?><a href="<?= url('watch/' . ($r['video_id'] ?? '')) ?>">Комментарий #<?= e($r['comment_id']) ?></a><?php endif; ?>
            </td>
            <td style="max-width:320px"><?= e(mb_substr($r['details'] ?? '', 0, 160)) ?></td>
            <td><?= e($r['reporter'] ?? 'гость') ?></td>
            <td>
                <div class="row">
                    <form action="<?= url('admin/reports/' . $r['id'] . '/resolve') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm btn-primary" type="submit">Закрыть</button></form>
                    <form action="<?= url('admin/reports/' . $r['id'] . '/dismiss') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm" type="submit">Отклонить</button></form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
