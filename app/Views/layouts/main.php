<?php
/** @var string $content */
/** @var string $title */
use App\Core\Csrf;
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e($title ?? 'GlassTube') ?></title>
    <link rel="stylesheet" href="<?= asset('css/glass.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
    <header class="container">
        <nav class="navbar">
            <a class="brand" href="<?= url('') ?>"><span class="dot"></span> GlassTube</a>
            <form class="nav-search" action="<?= url('search') ?>" method="get">
                <input class="input" type="search" name="q" placeholder="Спокойный поиск видео…" value="<?= e($_GET['q'] ?? '') ?>">
            </form>
            <div class="nav-links">
                <button id="theme-toggle" class="btn btn-sm btn-ghost" title="Тема">🌙</button>
                <?php if ($user): ?>
                    <a href="<?= url('upload') ?>">Загрузить</a>
                    <a href="<?= url('subscriptions') ?>">Подписки</a>
                    <a href="<?= url('history') ?>">История</a>
                    <?php if (is_admin()): ?><a href="<?= url('admin') ?>">Админ</a><?php endif; ?>
                    <a href="<?= url('settings') ?>"><?= e($user['username']) ?></a>
                    <form action="<?= url('logout') ?>" method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm" type="submit">Выйти</button>
                    </form>
                <?php else: ?>
                    <a href="<?= url('login') ?>">Вход</a>
                    <a class="btn btn-sm btn-primary" href="<?= url('register') ?>">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <?php $flash = flash(); ?>
    <?php if ($flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['text']) ?></div>
    <?php endif; ?>

    <main class="container page">
        <?= $content ?>
    </main>

    <footer class="container" style="padding:28px 0;color:var(--muted);font-size:.85rem">
        <div class="row">
            <span>GlassTube — спокойный видеохостинг.</span>
            <span class="spacer"></span>
            <a href="<?= url('rules') ?>">Правила</a>
            <a href="<?= url('search') ?>">Поиск</a>
        </div>
    </footer>

    <script type="module" src="<?= asset('js/app.js') ?>"></script>
    <script type="module" src="<?= asset('js/upload.js') ?>"></script>
    <script type="module" src="<?= asset('js/player.js') ?>"></script>
    <script type="module" src="<?= asset('js/interactions.js') ?>"></script>
    <script type="module" src="<?= asset('js/ai.js') ?>"></script>
</body>
</html>
