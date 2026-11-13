// GlassTube — shared helpers + theme + flash (ES module)
export function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export async function postJson(url, data = {}) {
    const fd = new FormData();
    for (const [k, v] of Object.entries(data)) {
        fd.append(k, v);
    }
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
    });
    let json = {};
    try { json = await res.json(); } catch (_) {}
    return { ok: res.ok, status: res.status, json };
}

export async function postJsonRaw(url, payload) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });
    let json = {};
    try { json = await res.json(); } catch (_) {}
    return { ok: res.ok, status: res.status, json };
}

// Theme toggle
(function () {
    const saved = localStorage.getItem('gt-theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
    }
    const btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', () => {
            const cur = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', cur);
            localStorage.setItem('gt-theme', cur);
            btn.textContent = cur === 'light' ? '🌙' : '☀️';
        });
    }
})();

// Auto-dismiss flash messages
document.querySelectorAll('.flash').forEach((el) => {
    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    }, 4500);
});
