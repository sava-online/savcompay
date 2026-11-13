<?php
/** @var array $video */
/** @var array $comments */
/** @var int $comment_page */
/** @var array $tags */
/** @var bool $liked */
/** @var bool $saved */
/** @var bool $subscribed */
/** @var array $similar */
/** @var bool $is_owner */
$user = auth_user();
$isAdmin = is_admin();
$aiEnabled = config('ENABLE_AI', 'true') === 'true';
$statusNote = [
    'processing' => 'Видео обрабатывается и пока не видно в каталоге.',
    'pending_review' => 'Видео на проверке модератором.',
    'blocked' => 'Видео заблокировано.',
];
?>
<div class="breadcrumbs">
    <a href="<?= url('') ?>">Главная</a> / <a href="<?= url('channel/' . $video['channel_slug']) ?>"><?= e($video['channel_name']) ?></a> / <?= e($video['title']) ?>
</div>

<div class="watch-layout">
    <div>
        <div class="player-wrap">
            <video id="gt-player" data-video-id="<?= e($video['id']) ?>" controls preload="metadata"
                   poster="<?= !empty($video['thumbnail_path']) ? '/stream/thumbnail/' . rawurlencode(basename($video['thumbnail_path'])) : '' ?>">
                <source src="<?= url('video/' . $video['id'] . '/stream') ?>" type="<?= e($video['mime_type']) ?>">
                Ваш браузер не поддерживает видео.
            </video>
        </div>

        <h1 class="watch-title"><?= e($video['title']) ?></h1>
        <div class="watch-sub">
            <span><?= e($video['channel_name']) ?></span>
            <span>· <?= e(format_number((int)$video['view_count'])) ?> просмотров</span>
            <span>· <?= e(format_date($video['published_at'] ?? $video['created_at'])) ?></span>
            <?php if (!empty($video['category_name'])): ?><span class="badge"><?= e($video['category_name']) ?></span><?php endif; ?>
            <?php if ($video['status'] !== 'published'): ?><span class="badge" style="color:var(--warning)"><?= e($statusNote[$video['status']] ?? $video['status']) ?></span><?php endif; ?>
        </div>

        <div class="action-bar">
            <?php if ($user): ?>
                <button class="btn btn-sm <?= $liked ? 'active' : '' ?>" data-action="like" data-id="<?= e($video['id']) ?>">
                    <?= $liked ? '❤️' : '🤍' ?> <span class="like-count"><?= e(format_number((int)$video['like_count'])) ?></span>
                </button>
                <button class="btn btn-sm <?= $saved ? 'active' : '' ?>" data-action="save" data-id="<?= e($video['id']) ?>">
                    <?= $saved ? '🔖 Сохранено' : '🔖 Сохранить' ?>
                </button>
                <?php if (!$is_owner): ?>
                    <button class="btn btn-sm" data-action="subscribe" data-id="<?= e($video['channel_id']) ?>">
                        <?= $subscribed ? '✓ Вы подписаны' : '＋ Подписаться' ?>
                    </button>
                <?php endif; ?>
                <button class="btn btn-sm" data-action="report" data-type="video" data-id="<?= e($video['id']) ?>">⚑ Пожаловаться</button>
            <?php else: ?>
                <a class="btn btn-sm" href="<?= url('login') ?>">🤍 Лайк</a>
                <a class="btn btn-sm" href="<?= url('login') ?>">🔖 Сохранить</a>
            <?php endif; ?>
            <span class="spacer"></span>
            <button id="quiet-toggle" class="btn btn-sm">🔈 Тихий режим</button>
        </div>

        <div class="description-box" id="desc-box">
            <?= e($video['description'] ?: 'Описание отсутствует.') ?>
        </div>
        <?php if (!empty($video['description']) && mb_strlen($video['description']) > 180): ?>
            <button id="desc-toggle" class="btn btn-sm btn-ghost mt-1">Показать полностью</button>
        <?php endif; ?>

        <?php if (!empty($tags)): ?>
            <div class="tag-list">
                <?php foreach ($tags as $t): ?><a class="chip" href="<?= url('tag/' . $t['slug']) ?>">#<?= e($t['name']) ?></a><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- AI assistant -->
        <div class="ai-panel quiet-hide">
            <h4>✨ ИИ-помощник</h4>
            <?php if (!$aiEnabled): ?>
                <p class="muted text-sm">ИИ-помощник временно отключён администратором.</p>
            <?php else: ?>
                <p class="muted text-sm">Пусть ИИ кратко объяснит содержание и поможет найти похожее.</p>
                <div class="ai-actions">
                    <button class="btn btn-sm" data-ai="summarize" data-video-id="<?= e($video['id']) ?>">Суммировать</button>
                    <button class="btn btn-sm" data-ai="explain" data-video-id="<?= e($video['id']) ?>">Объяснить тему</button>
                    <button class="btn btn-sm" data-ai="similar" data-title="<?= e($video['title']) ?>">Похожие видео</button>
                </div>
                <div id="ai-result"></div>
            <?php endif; ?>
        </div>

        <!-- Comments -->
        <section class="section quiet-hide">
            <div class="section-head"><h2 class="section-title">Комментарии</h2></div>

            <?php if ($user): ?>
                <form class="comment-form glass" id="comment-form" data-video-id="<?= e($video['id']) ?>" style="padding:14px">
                    <div class="field" style="margin:0">
                        <textarea class="input" name="body" placeholder="Поделитесь мыслями спокойно и дружелюбно…" required></textarea>
                    </div>
                    <div class="text-right mt-1">
                        <button class="btn btn-primary btn-sm" type="submit">Отправить</button>
                    </div>
                    <div id="comment-msg" class="muted text-sm mt-1"></div>
                </form>
            <?php else: ?>
                <p class="muted"><a href="<?= url('login') ?>">Войдите</a>, чтобы оставить комментарий.</p>
            <?php endif; ?>

            <div id="comment-list">
                <?php if (empty($comments)): ?>
                    <p class="muted">Пока нет комментариев. Будьте первым.</p>
                <?php else: ?>
                    <?php foreach ($comments as $c):
                        $canDelete = $user && ($isAdmin || $is_owner || (int)$c['user_id'] === (int)$user['id']);
                    ?>
                        <div class="comment <?= $c['status'] !== 'visible' ? 'hidden-comment' : '' ?>" id="comment-<?= e($c['id']) ?>">
                            <img class="avatar" src="/stream/avatar/<?= rawurlencode($c['username']) ?>" alt="">
                            <div class="body">
                                <div class="name"><?= e($c['channel_name'] ?: $c['username']) ?> <span class="time"><?= e(format_date($c['created_at'])) ?></span></div>
                                <div class="text"><?= e($c['body']) ?></div>
                                <?php if ($c['status'] !== 'visible'): ?><div class="muted text-sm">Комментарий скрыт модерацией.</div><?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <button class="btn btn-sm btn-ghost mt-1" data-action="delete-comment" data-id="<?= e($c['id']) ?>">Удалить</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="mt-1">
                <a class="chip" href="<?= url('watch/' . $video['id'] . '?cpage=' . ($comment_page + 1)) ?>">Загрузить ещё комментарии</a>
            </div>
        </section>
    </div>

    <!-- Sidebar -->
    <aside>
        <div class="side-card glass">
            <div class="flex items-center gap-1">
                <img class="avatar lg" src="/stream/avatar/<?= rawurlencode($video['channel_name']) ?>" alt="">
                <div>
                    <div style="font-weight:650"><a href="<?= url('channel/' . $video['channel_slug']) ?>"><?= e($video['channel_name']) ?></a></div>
                    <div class="muted text-sm">Канал</div>
                </div>
            </div>
            <a class="btn btn-sm btn-ghost mt-1" style="width:100%" href="<?= url('channel/' . $video['channel_slug']) ?>">Открыть канал</a>
        </div>

        <div class="side-card glass">
            <div class="section-title" style="font-size:1rem">Похожие видео</div>
            <?php if (empty($similar)): ?>
                <p class="muted text-sm mt-1">Нет похожих.</p>
            <?php else: ?>
                <?php foreach ($similar as $v): ?>
                    <a class="flex gap-1 mt-1" href="<?= url('watch/' . $v['id']) ?>" style="align-items:center">
                        <div class="thumb" style="width:120px;flex:0 0 120px">
                            <?php if (!empty($v['thumbnail_path'])): ?><img src="/stream/thumbnail/<?= rawurlencode(basename($v['thumbnail_path'])) ?>" alt=""><?php endif; ?>
                        </div>
                        <div style="font-size:.85rem"><?= e($v['title']) ?><div class="muted"><?= e($v['channel_name']) ?></div></div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>

<!-- Report modal -->
<div class="modal-backdrop" id="report-modal">
    <div class="modal">
        <h3>Пожаловаться</h3>
        <form id="report-form">
            <input type="hidden" name="video_id" id="report-video-id">
            <input type="hidden" name="comment_id" id="report-comment-id">
            <div class="field">
                <label>Причина</label>
                <select class="input" name="reason" required>
                    <option value="spam">Спам</option>
                    <option value="insult">Оскорбления</option>
                    <option value="copyright">Нарушение авторских прав</option>
                    <option value="inappropriate">Неприемлемый контент</option>
                    <option value="other">Другое</option>
                </select>
            </div>
            <div class="field">
                <label>Подробности (необязательно)</label>
                <textarea class="input" name="details" maxlength="1000"></textarea>
            </div>
            <div id="report-msg" class="muted text-sm"></div>
            <div class="row mt-1">
                <button type="submit" class="btn btn-primary btn-sm">Отправить</button>
                <button type="button" class="btn btn-sm" id="report-close">Отмена</button>
            </div>
        </form>
    </div>
</div>
