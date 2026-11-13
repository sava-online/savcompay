<?php
/** @var array $videos */
?>
<div class="breadcrumbs"><a href="<?= url('admin') ?>">Админ</a> / Видео</div>
<h1 class="section-title">Видео</h1>
<div class="glass-strong" style="padding:8px;overflow-x:auto">
<table class="table">
    <thead><tr><th>ID</th><th>Название</th><th>Канал</th><th>Статус</th><th>Видимость</th><th>Просм.</th><th>Действия</th></tr></thead>
    <tbody>
    <?php foreach ($videos as $v): ?>
        <tr>
            <td><?= e($v['id']) ?></td>
            <td><a href="<?= url('watch/' . $v['id']) ?>"><?= e($v['title']) ?></a></td>
            <td><?= e($v['channel_name']) ?></td>
            <td><?= e($v['status']) ?></td>
            <td><?= e($v['visibility']) ?></td>
            <td><?= e($v['view_count']) ?></td>
            <td>
                <div class="row">
                    <?php if ($v['status'] !== 'published'): ?><form action="<?= url('admin/videos/' . $v['id'] . '/publish') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm btn-primary" type="submit">Опубликовать</button></form><?php endif; ?>
                    <?php if ($v['status'] !== 'blocked'): ?><form action="<?= url('admin/videos/' . $v['id'] . '/block') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm" type="submit">Заблокировать</button></form><?php endif; ?>
                    <form action="<?= url('admin/videos/' . $v['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Удалить видео?');"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Удалить</button></form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
