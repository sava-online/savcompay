<?php
/** @var array $category */
/** @var array $videos */
/** @var string $sort */
/** @var array $categories */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / Категории / <?= e($category['name']) ?></div>
<h1 class="section-title"><?= e($category['name']) ?></h1>

<div class="row mb-1">
    <?php foreach ($categories as $c): ?>
        <a class="chip <?= $c['id'] == $category['id'] ? 'active' : '' ?>" href="<?= url('category/' . $c['slug']) ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="row mb-1">
    <span class="muted text-sm">Сортировка:</span>
    <a class="chip <?= $sort === 'new' ? 'active' : '' ?>" href="<?= url('category/' . $category['slug'] . '?sort=new') ?>">Новые</a>
    <a class="chip <?= $sort === 'popular' ? 'active' : '' ?>" href="<?= url('category/' . $category['slug'] . '?sort=popular') ?>">Популярные</a>
</div>

<?php if (empty($videos)): ?>
    <div class="empty">В этой категории пока нет видео.</div>
<?php else: ?>
    <div class="grid mt-1">
        <?php foreach ($videos as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
    </div>
<?php endif; ?>
