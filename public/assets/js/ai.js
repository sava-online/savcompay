// GlassTube — AI assistant interactions
import { csrf, postJsonRaw } from './app.js';

function loading(el) {
    el.innerHTML = '<div class="ai-loading">ИИ-помощник думает…</div>';
}
function showError(el, msg) {
    el.innerHTML = '<div class="ai-result" style="border-color:var(--danger)">' +
        (msg || 'ИИ-помощник временно недоступен.') + '</div>';
}

// ---- Upload page: generate metadata ----
const genBtn = document.querySelector('[data-ai="generate-metadata"]');
if (genBtn) {
    genBtn.addEventListener('click', async () => {
        const out = document.getElementById('ai-meta-result');
        loading(out);
        const payload = {
            title: document.getElementById('title')?.value || '',
            description: document.getElementById('description')?.value || '',
            category: document.getElementById('category_id')?.value || '',
            tags: document.getElementById('tags')?.value || '',
        };
        const { ok, json } = await postJsonRaw('/api/ai/generate-metadata', payload);
        if (!ok || json.error) {
            showError(out, 'Не удалось сгенерировать метаданные.');
            return;
        }
        if (document.getElementById('title') && json.title) document.getElementById('title').value = json.title;
        if (document.getElementById('description') && json.description != null) document.getElementById('description').value = json.description;
        if (document.getElementById('tags') && Array.isArray(json.tags)) document.getElementById('tags').value = json.tags.join(', ');
        out.innerHTML = '<div class="ai-result">✨ Метаданные обновлены' +
            (json._fallback ? ' (резервный режим)' : '') + '.</div>';
    });
}

// ---- Upload page: suggest tags ----
const tagBtn = document.querySelector('[data-ai="suggest-tags"]');
if (tagBtn) {
    tagBtn.addEventListener('click', async () => {
        const out = document.getElementById('ai-meta-result');
        loading(out);
        const payload = {
            title: document.getElementById('title')?.value || '',
            description: document.getElementById('description')?.value || '',
        };
        const { ok, json } = await postJsonRaw('/api/ai/suggest-tags', payload);
        if (!ok || !Array.isArray(json.tags)) {
            showError(out, 'Не удалось предложить теги.');
            return;
        }
        const cur = (document.getElementById('tags')?.value || '').split(',').map(s => s.trim()).filter(Boolean);
        const merged = Array.from(new Set([...cur, ...json.tags])).slice(0, 10);
        if (document.getElementById('tags')) document.getElementById('tags').value = merged.join(', ');
        out.innerHTML = '<div class="ai-result">🏷️ Добавлены теги: ' + json.tags.join(', ') + '</div>';
    });
}

// ---- Watch page: summarize / explain ----
function wireSummary(button, videoId) {
    button.addEventListener('click', async () => {
        const out = document.getElementById('ai-result');
        loading(out);
        const { ok, json } = await postJsonRaw('/api/ai/summarize-video', { video_id: videoId });
        if (!ok || json.error) {
            showError(out, 'Не удалось построить summary.');
            return;
        }
        let html = '<div class="ai-result"><strong>Краткое описание</strong><br>' +
            escapeHtml(json.summary || '') + '</div>';
        if (Array.isArray(json.topics) && json.topics.length) {
            html += '<div class="ai-result mt-1"><strong>Темы:</strong> ' + json.topics.map(escapeHtml).join(', ') + '</div>';
        }
        if (Array.isArray(json.chapters) && json.chapters.length) {
            html += '<div class="ai-result mt-1"><strong>Главы:</strong><ul style="margin:6px 0 0 18px">' +
                json.chapters.map(c => '<li>' + (c.time || 0) + 's — ' + escapeHtml(c.title || '') + '</li>').join('') +
                '</ul></div>';
        }
        if (json._fallback) {
            html += '<div class="ai-result mt-1 muted">⚠️ Резервный режим: ИИ недоступен или отключён.</div>';
        }
        out.innerHTML = html;
    });
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
}

const summarizeBtn = document.querySelector('[data-ai="summarize"]');
if (summarizeBtn) {
    const id = summarizeBtn.dataset.videoId;
    wireSummary(summarizeBtn, id);
}
const explainBtn = document.querySelector('[data-ai="explain"]');
if (explainBtn) {
    const id = explainBtn.dataset.videoId;
    wireSummary(explainBtn, id);
}

// ---- Watch page: similar videos (smart search redirect) ----
const similarBtn = document.querySelector('[data-ai="similar"]');
if (similarBtn) {
    similarBtn.addEventListener('click', () => {
        const q = similarBtn.dataset.title || '';
        location.href = '/search?q=' + encodeURIComponent(q) + '&ai=1';
    });
}
