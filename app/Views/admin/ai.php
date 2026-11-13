<?php
/** @var array $logs */
?>
<div class="breadcrumbs"><a href="<?= url('admin') ?>">Админ</a> / ИИ-логи</div>
<h1 class="section-title">ИИ-запросы</h1>
<div class="glass-strong" style="padding:8px;overflow-x:auto">
<table class="table">
    <thead><tr><th>Время</th><th>Эндпоинт</th><th>Провайдер</th><th>Модель</th><th>Статус</th><th>Время</th><th>Ошибка</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
        <tr>
            <td><?= e($l['created_at']) ?></td>
            <td><?= e($l['endpoint']) ?></td>
            <td><?= e($l['provider']) ?></td>
            <td><?= e($l['model']) ?></td>
            <td><?= e($l['status']) ?></td>
            <td><?= e($l['response_time_ms'] ?? '') ?> мс</td>
            <td><?= e($l['error_message'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
