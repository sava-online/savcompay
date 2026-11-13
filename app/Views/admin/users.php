<?php
/** @var array $users */
?>
<div class="breadcrumbs"><a href="<?= url('admin') ?>">Админ</a> / Пользователи</div>
<h1 class="section-title">Пользователи</h1>
<div class="glass-strong" style="padding:8px;overflow-x:auto">
<table class="table">
    <thead><tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Активен</th><th>Видео</th><th>Действия</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['id']) ?></td>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= $u['is_active'] ? '✓' : '✗' ?></td>
            <td><?= e($u['video_count']) ?></td>
            <td>
                <div class="row">
                    <form action="<?= url('admin/users/' . $u['id'] . '/toggle-active') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm" type="submit"><?= $u['is_active'] ? 'Деактивировать' : 'Активировать' ?></button></form>
                    <form action="<?= url('admin/users/' . $u['id'] . '/toggle-admin') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm" type="submit"><?= $u['role'] === 'admin' ? 'Снять админа' : 'Сделать админом' ?></button></form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
