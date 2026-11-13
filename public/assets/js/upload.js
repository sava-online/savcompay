// GlassTube — upload with progress + client-side validation
import { csrf, postJson } from './app.js';

const form = document.getElementById('upload-form');
if (form) {
    const fileInput = form.querySelector('#video_file');
    const bar = document.getElementById('upload-progress');
    const barWrap = document.getElementById('upload-progress-wrap');
    const submitBtn = form.querySelector('button[type="submit"]');
    const errBox = document.getElementById('upload-error');

    const ALLOWED = (window.GT_ALLOWED_EXT || 'mp4,webm').split(',');
    const MAX_MB = Number(window.GT_MAX_MB || 1024);

    fileInput?.addEventListener('change', () => {
        errBox.textContent = '';
        const f = fileInput.files[0];
        if (!f) return;
        const ext = f.name.split('.').pop().toLowerCase();
        if (!ALLOWED.includes(ext)) {
            errBox.textContent = 'Недопустимое расширение. Разрешены: ' + ALLOWED.join(', ');
            fileInput.value = '';
            return;
        }
        if (f.size > MAX_MB * 1024 * 1024) {
            errBox.textContent = 'Файл слишком большой (максимум ' + MAX_MB + ' МБ).';
            fileInput.value = '';
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        errBox.textContent = '';
        const f = fileInput?.files[0];
        if (!f) {
            errBox.textContent = 'Выберите видеофайл.';
            return;
        }
        const fd = new FormData(form);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.getAttribute('action') || location.pathname);
        xhr.setRequestHeader('X-CSRF-Token', csrf());
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = (ev) => {
            if (ev.lengthComputable) {
                const pct = Math.round((ev.loaded / ev.total) * 100);
                if (barWrap) barWrap.style.display = 'block';
                if (bar) bar.style.width = pct + '%';
            }
        };
        xhr.onload = () => {
            let data = {};
            try { data = JSON.parse(xhr.responseText); } catch (_) {}
            if (xhr.status >= 200 && xhr.status < 300 && data.ok) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Готово…';
                location.href = data.redirect;
            } else {
                errBox.textContent = data.error || 'Ошибка загрузки.';
                if (bar) bar.style.width = '0%';
                if (barWrap) barWrap.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Загрузить видео';
            }
        };
        xhr.onerror = () => {
            errBox.textContent = 'Сетевая ошибка при загрузке.';
            submitBtn.disabled = false;
        };
        submitBtn.disabled = true;
        submitBtn.textContent = 'Загрузка…';
        xhr.send(fd);
    });
}
