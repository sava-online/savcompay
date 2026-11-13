<?php
/** @var array $stats */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / Админ</div>
<h1 class="section-title">Дашборд</h1>

<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
    <a class="glass side-card" href="<?= url('admin/users') ?>"><div class="kpi"><span class="n"><?= e($stats['users']) ?></span><span class="l">Пользователи</span></div></a>
    <a class="glass side-card" href="<?= url('admin/videos') ?>"><div class="kpi"><span class="n"><?= e($stats['videos']) ?></span><span class="l">Видео</span></div></a>
    <a class="glass side-card" href="<?= url('admin/comments') ?>"><div class="kpi"><span class="n"><?= e($stats['comments']) ?></span><span class="l">Комментарии</span></div></a>
    <a class="glass side-card" href="<?= url('admin/reports') ?>"><div class="kpi"><span class="n"><?= e($stats['reports']) ?></span><span class="l">Открытые жалобы</span></div></a>
    <a class="glass side-card" href="<?= url('admin/ai') ?>"><div class="kpi"><span class="n"><?= e($stats['ai']) ?></span><span class="l">ИИ-запросы</span></div></a>
</div>

<div class="row mt-2">
    <a class="btn" href="<?= url('admin/settings') ?>">Настройки</a>
</div>
