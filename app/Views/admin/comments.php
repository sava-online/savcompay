<?php
/** @var array $comments */
?>
<div class="breadcrumbs"><a href="<?= url('admin') ?>">Админ</a> / Комментарии</div>
<h1 class="section-title">Комментарии</h1>
<div class="glass-strong" style="padding:8px;overflow-x:auto">
<table class="table">
    <thead><tr><th>ID</th><th>Автор</th><th>Видео</th><th>Текст</th><th>Статус</th><th>Действия</th></tr></thead>
    <tbody>
    <?php foreach ($comments as $c): ?>
        <tr>
            <td><?= e($c['id']) ?></td>
            <td><?= e($c['username']) ?></td>
            <td><a href="<?= url('watch/' . $c['video_id']) ?>"><?= e(mb_substr($c['video_title'], 0, 40)) ?></a></td>
            <td style="max-width:360px"><?= e(mb_substr($c['body'], 0, 160)) ?></td>
            <td><?= e($c['status']) ?></td>
            <td>
                <div class="row">
                    <?php if ($c['status'] !== 'visible'): ?><form action="<?= url('admin/comments/' . $c['id'] . '/restore') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm btn-primary" type="submit">Восстановить</button></form><?php endif; ?>
                    <?php if ($c['status'] !== 'hidden'): ?><form action="<?= url('admin/comments/' . $c['id'] . '/hide') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm" type="submit">Скрыть</button></form><?php endif; ?>
                    <form action="<?= url('admin/comments/' . $c['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Удалить?');"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Удалить</button></form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
