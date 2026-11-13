// GlassTube — UI interactions (like, save, subscribe, report, comments)
import { csrf, postJson } from './app.js';

// Like
document.querySelectorAll('[data-action="like"]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const { ok, json } = await postJson('/api/videos/' + id + '/like', {});
        if (ok && json.ok !== undefined) {
            btn.classList.toggle('active', json.liked);
            btn.innerHTML = (json.liked ? '❤️ ' : '🤍 ') + '<span class="like-count">' + json.count + '</span>';
        }
    });
});

// Save (watch later)
document.querySelectorAll('[data-action="save"]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const { ok, json } = await postJson('/api/videos/' + id + '/save', {});
        if (ok && json.ok !== undefined) {
            btn.classList.toggle('active', json.saved);
            btn.textContent = json.saved ? '🔖 Сохранено' : '🔖 Сохранить';
        }
    });
});

// Subscribe
document.querySelectorAll('[data-action="subscribe"]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const { ok, json } = await postJson('/api/channels/' + id + '/subscribe', {});
        if (ok && json.subscribed !== undefined) {
            btn.classList.toggle('active', json.subscribed);
            btn.textContent = json.subscribed ? '✓ Вы подписаны' : '＋ Подписаться';
        }
    });
});

// Report modal
const reportModal = document.getElementById('report-modal');
const reportForm = document.getElementById('report-form');
if (reportModal && reportForm) {
    document.querySelectorAll('[data-action="report"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.getElementById('report-video-id').value = btn.dataset.type === 'video' ? btn.dataset.id : '';
            document.getElementById('report-comment-id').value = btn.dataset.type === 'comment' ? btn.dataset.id : '';
            reportModal.classList.add('open');
        });
    });
    document.getElementById('report-close').addEventListener('click', () => reportModal.classList.remove('open'));
    reportModal.addEventListener('click', (e) => { if (e.target === reportModal) reportModal.classList.remove('open'); });
    reportForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(reportForm);
        const { ok, json } = await postJson('/api/reports', Object.fromEntries(fd));
        const msg = document.getElementById('report-msg');
        if (ok) {
            msg.style.color = 'var(--success)';
            msg.textContent = json.message || 'Жалоба отправлена.';
            setTimeout(() => reportModal.classList.remove('open'), 1200);
        } else {
            msg.style.color = 'var(--danger)';
            msg.textContent = json.error || 'Не удалось отправить жалобу.';
        }
    });
}

// Comments
const commentForm = document.getElementById('comment-form');
if (commentForm) {
    const videoId = commentForm.dataset.videoId;
    const list = document.getElementById('comment-list');
    const msg = document.getElementById('comment-msg');
    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = commentForm.querySelector('textarea').value.trim();
        if (!body) return;
        const { ok, json } = await postJson('/api/comments/' + videoId, { body });
        if (ok) {
            commentForm.querySelector('textarea').value = '';
            if (json.hidden) {
                msg.style.color = 'var(--warning)';
                msg.textContent = json.message || 'Комментарий скрыт модерацией.';
            } else {
                msg.style.color = 'var(--success)';
                msg.textContent = 'Комментарий добавлен.';
                const placeholder = list.querySelector('p.muted');
                if (placeholder) placeholder.remove();
            }
        } else {
            msg.style.color = 'var(--danger)';
            msg.textContent = json.error || 'Ошибка отправки.';
        }
    });
}

// Delete comment
document.querySelectorAll('[data-action="delete-comment"]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        if (!confirm('Удалить комментарий?')) return;
        const { ok } = await postJson('/api/comments/' + id + '/delete', {});
        if (ok) {
            const el = document.getElementById('comment-' + id);
            if (el) el.remove();
        }
    });
});
