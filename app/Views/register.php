<?php
/** @var string $title */
?>
<div class="auth-wrap glass-strong">
    <h1>Регистрация</h1>
    <p class="muted">Создайте аккаунт и свой канал в GlassTube.</p>
    <form action="<?= url('register') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label>Логин</label>
            <input class="input" type="text" name="username" value="<?= e(old('username')) ?>" minlength="3" maxlength="40" autofocus>
        </div>
        <div class="field">
            <label>Email</label>
            <input class="input" type="email" name="email" value="<?= e(old('email')) ?>">
        </div>
        <div class="field">
            <label>Пароль (минимум 8 символов)</label>
            <input class="input" type="password" name="password" minlength="8">
        </div>
        <div class="field">
            <label>Повторите пароль</label>
            <input class="input" type="password" name="password2" minlength="8">
        </div>
        <div class="field checkbox-row">
            <input type="checkbox" name="accept_terms" value="1" id="accept">
            <label for="accept" style="color:var(--text)">Я принимаю <a href="<?= url('rules') ?>">правила сообщества</a> и подтверждаю, что обладаю правами на загружаемый контент.</label>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%">Создать аккаунт</button>
    </form>
    <p class="mt-1 muted">Уже есть аккаунт? <a href="<?= url('login') ?>">Войдите</a>.</p>
</div>
