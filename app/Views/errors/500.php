<?php
/** @var string $title */
/** @var bool $debug */
/** @var string $message */
?>
<div class="empty" style="margin-top:60px">
    <div style="font-size:3rem;font-weight:800">500</div>
    <h2 style="margin:8px 0">Внутренняя ошибка</h2>
    <p class="muted">Что-то пошло не так. Мы уже разбираемся.</p>
    <?php if (!empty($debug)): ?>
        <pre class="code" style="margin-top:14px;text-align:left;white-space:pre-wrap"><?= e($message) ?></pre>
    <?php endif; ?>
    <div class="row" style="justify-content:center;margin-top:14px">
        <a class="btn btn-primary" href="<?= url('') ?>">На главную</a>
    </div>
</div>
