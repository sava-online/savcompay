<?php
/** @var string $title */
?>
<div class="auth-wrap glass-strong">
    <h1>Вход в GlassTube</h1>
    <p class="muted">Спокойный просмотр начинается здесь.</p>
    <form action="<?= url('login') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Email или логин</label>
            <input class="input" type="text" name="login" value="<?= e(old('login')) ?>" autofocus>
        </div>
        <div class="field">
            <label>Пароль</label>
            <input class="input" type="password" name="password">
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%">Войти</button>
    </form>
    <p class="mt-1 muted">Нет аккаунта? <a href="<?= url('register') ?>">Зарегистрируйтесь</a>.</p>
</div>
