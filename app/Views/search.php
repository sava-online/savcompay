<?php
/** @var string $q */
/** @var string $category */
/** @var string $sort */
/** @var bool $use_ai */
/** @var string $explanation */
/** @var array $categories */
/** @var array $results */
/** @var int $total */
?>
<div class="breadcrumbs"><a href="<?= url('') ?>">Главная</a> / Поиск</div>
<h1 class="section-title">Поиск</h1>

<form action="<?= url('search') ?>" method="get" class="glass-strong" style="padding:16px;margin-bottom:18px">
    <div class="row">
        <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Что хотите посмотреть?" style="flex:1">
        <button class="btn btn-primary" type="submit">Найти</button>
    </div>
    <div class="row mt-1">
        <label class="chip">Категория:
            <select class="input" name="category" style="width:auto;display:inline-block;margin-left:6px" onchange="this.form.submit()">
                <option value="">Все</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= (string)$category === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="chip">Сортировка:
            <select class="input" name="sort" style="width:auto;display:inline-block;margin-left:6px" onchange="this.form.submit()">
                <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>По релевантности</option>
                <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Новые</option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Популярные</option>
            </select>
        </label>
        <label class="chip">
            <input type="checkbox" name="ai" value="1" <?= $use_ai ? 'checked' : '' ?> onchange="this.form.submit()"> ИИ-поиск
        </label>
    </div>
</form>

<?php if ($q !== '' && $use_ai && $explanation): ?>
    <div class="ai-panel"><strong>ИИ уточнил запрос:</strong> <?= e($explanation) ?></div>
<?php endif; ?>

<?php if ($q !== ''): ?>
    <p class="muted">Найдено видео: <?= e($total) ?></p>
    <?php if (empty($results)): ?>
        <div class="empty">Ничего не нашлось. Попробуйте другие слова или категорию.</div>
    <?php else: ?>
        <div class="grid mt-1">
            <?php foreach ($results as $v): ?><?= \App\Core\View::render('partials/video_card', ['video' => $v]) ?><?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="empty">Введите запрос, чтобы начать спокойный поиск.</div>
<?php endif; ?>
