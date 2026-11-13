// GlassTube — player: save watch progress + quiet mode
import { csrf } from './app.js';

const video = document.getElementById('gt-player');
if (video) {
    const videoId = video.dataset.videoId;
    let lastSave = 0;
    let lastPct = 0;

    video.addEventListener('timeupdate', () => {
        const now = Date.now();
        // Save at most every ~10s and on meaningful progress.
        if (now - lastSave > 10000) {
            lastSave = now;
            saveProgress(Math.floor(video.currentTime));
        }
    });

    function saveProgress(seconds) {
        if (!videoId) return;
        const fd = new FormData();
        fd.append('seconds', String(seconds));
        fetch('/api/videos/' + videoId + '/progress', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        }).catch(() => {});
    }

    window.addEventListener('beforeunload', () => {
        if (videoId) {
            const fd = new FormData();
            fd.append('seconds', String(Math.floor(video.currentTime)));
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/api/videos/' + videoId + '/progress', fd);
            }
        }
    });
}

// Quiet mode toggle
const quietBtn = document.getElementById('quiet-toggle');
if (quietBtn) {
    quietBtn.addEventListener('click', () => {
        const on = document.body.classList.toggle('quiet');
        localStorage.setItem('gt-quiet', on ? '1' : '0');
        quietBtn.textContent = on ? '🔊 Тихий режим: вкл' : '🔈 Тихий режим';
        quietBtn.classList.toggle('active', on);
    });
    if (localStorage.getItem('gt-quiet') === '1') {
        document.body.classList.add('quiet');
        quietBtn.classList.add('active');
        quietBtn.textContent = '🔊 Тихий режим: вкл';
    }
}

// Expand description
const descToggle = document.getElementById('desc-toggle');
const descBox = document.getElementById('desc-box');
if (descToggle && descBox) {
    descToggle.addEventListener('click', () => {
        const expanded = descBox.classList.toggle('expanded');
        descToggle.textContent = expanded ? 'Свернуть' : 'Показать полностью';
    });
}
