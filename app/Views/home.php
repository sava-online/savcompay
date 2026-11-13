<?php
/** @var array $new */
/** @var array $popular */
/** @var array $categories */
/** @var array $continue */
/** @var int $page */
/** @var bool $has_more */
?>
<?php if (!empty($continue)): ?>
<section class="section">
    <div class="section-head"><h2 class="section-title">Продолжить просмотр</h2></div>
    <div class="grid">
        <?php foreach ($continue as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="section-head">
        <h2 class="section-title">Новое</h2>
        <a class="chip" href="<?= url('search') ?>">Все видео →</a>
    </div>
    <div class="grid">
        <?php foreach ($new as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
    </div>
    <?php if ($has_more): ?>
        <div class="text-center mt-2">
            <a class="btn" href="<?= url('?page=' . ($page + 1)) ?>">Загрузить ещё</a>
        </div>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head"><h2 class="section-title">Категории</h2></div>
    <div class="row">
        <?php foreach ($categories as $c): ?>
            <a class="chip" href="<?= url('category/' . $c['slug']) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-head"><h2 class="section-title">Популярное</h2></div>
    <div class="grid">
        <?php foreach ($popular as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
    </div>
</section>
