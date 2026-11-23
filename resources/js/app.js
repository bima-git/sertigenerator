import './bootstrap';
import { initIssueForm } from './issue-form';

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

// Animasi masuk dan angka KPI ditangani CSS ([data-reveal], .count) agar tidak berkedip.

/* Sorot mengikuti kursor pada tile KPI. */
function initSpotlight() {
    if (reduce) return;
    document.addEventListener('pointermove', (e) => {
        const tile = e.target.closest?.('.tile');
        if (!tile) return;
        const r = tile.getBoundingClientRect();
        tile.style.setProperty('--mx', `${e.clientX - r.left}px`);
        tile.style.setProperty('--my', `${e.clientY - r.top}px`);
    }, { passive: true });
}

/* Pil sorot di sidebar meluncur ke tautan yang sedang ditunjuk. */
function initSideHover() {
    const nav = document.querySelector('[data-side-nav]');
    if (!nav) return;
    const pill = nav.querySelector('.side-hover');
    nav.addEventListener('pointerover', (e) => {
        const link = e.target.closest('.side-link');
        if (!link || link.getAttribute('aria-current') === 'page') { pill.style.opacity = 0; return; }
        pill.style.transform = `translateY(${link.offsetTop}px)`;
        pill.style.opacity = 1;
    });
    nav.addEventListener('pointerleave', () => (pill.style.opacity = 0));
}

/* Pop-up notifikasi di pojok kanan atas. Hilang sendiri (galat lebih lama); jeda saat ditunjuk kursor. */
const TOAST = {
    success: ['ph-check-circle', 'Berhasil', 5000],
    danger: ['ph-warning-octagon', 'Gagal', 9000],
    warning: ['ph-warning', 'Perhatian', 7000],
    info: ['ph-info', 'Info', 5000],
};
export function toast(message, variant = 'success') {
    const host = document.getElementById('toasts');
    if (!host) return;
    const [icon, title, dur] = TOAST[variant] ?? TOAST.info;
    const el = document.createElement('div');
    el.setAttribute('role', variant === 'danger' ? 'alert' : 'status');
    el.className = `toast toast-${variant}`;
    el.style.setProperty('--dur', `${dur}ms`);
    el.innerHTML = `<span class="modal-icon"><i class="ph-fill ${icon}"></i></span>
        <div class="min-w-0 flex-1"><p class="toast-title"></p><p class="toast-msg"></p></div>
        <button type="button" class="btn btn-icon !size-8 -mr-1 -mt-1 flex-none" aria-label="Tutup pesan"><i class="ph ph-x"></i></button>
        <span class="toast-bar" aria-hidden="true"></span>`;
    el.querySelector('.toast-title').textContent = title;
    el.querySelector('.toast-msg').textContent = message;

    const dismiss = () => {
        clearTimeout(timer);
        if (reduce) return el.remove();
        el.classList.add('is-leaving');
        el.addEventListener('animationend', () => el.remove(), { once: true });
    };
    let timer, left = dur, started;
    const run = () => { started = Date.now(); timer = setTimeout(dismiss, left); };
    el.addEventListener('mouseenter', () => { clearTimeout(timer); left -= Date.now() - started; });
    el.addEventListener('mouseleave', run);
    el.querySelector('button').onclick = dismiss;
    host.prepend(el);
    run();
}
window.toast = toast;

/* Pesan dari server (session success/error) tampil sebagai pop-up, bukan kotak di halaman. */
function initFlash() {
    $$('[data-flash]').forEach((el) => { toast(el.textContent.trim(), el.dataset.flash); el.remove(); });
}

/*
 * Dialog konfirmasi pengganti window.confirm. Mengembalikan Promise<boolean>.
 * tone 'danger' untuk aksi yang mengurangi/menghapus, 'brand' untuk aksi biasa.
 */
export function confirmDialog({ title, message, confirm = 'Lanjutkan', tone = 'danger', icon = 'ph-warning' }) {
    return new Promise((resolve) => {
        const dlg = document.createElement('dialog');
        dlg.className = `modal tone-${tone}`;
        dlg.setAttribute('aria-labelledby', 'confirm-title');
        dlg.innerHTML = `<div class="p-6 grid gap-6">
            <div class="flex items-start gap-4">
                <span class="modal-icon"><i class="ph ${icon}"></i></span>
                <div><h2 id="confirm-title" class="text-lg font-semibold"></h2><p class="mt-1 text-sm"></p></div>
            </div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-ghost" data-no>Batal</button>
                <button type="button" class="btn ${tone === 'danger' ? 'btn-danger' : 'btn-primary'}" data-yes></button>
            </div></div>`;
        dlg.querySelector('h2').textContent = title;
        dlg.querySelector('p').textContent = message;
        dlg.querySelector('[data-yes]').textContent = confirm;
        dlg.querySelector('[data-yes]').onclick = () => dlg.close('yes');
        dlg.querySelector('[data-no]').onclick = () => dlg.close();
        dlg.addEventListener('close', () => { resolve(dlg.returnValue === 'yes'); dlg.remove(); });
        document.body.append(dlg);
        dlg.showModal();
        // Fokus awal di Batal untuk aksi berbahaya, supaya Enter tidak langsung mengeksekusi.
        dlg.querySelector(tone === 'danger' ? '[data-no]' : '[data-yes]').focus();
    });
}

/* Tombol memuat: teks berganti kata kerja berjalan dan terkunci (Design System 7.2). */
export function setLoading(btn, on) {
    const label = btn.querySelector('[data-label]');
    const icon = btn.querySelector('i');
    if (on) {
        btn.dataset.idle = label.textContent;
        if (icon) btn.dataset.idleIcon = icon.className;
        btn.style.minWidth = `${btn.offsetWidth}px`;
        label.textContent = btn.dataset.loadingText || 'Memproses';
        if (icon) icon.className = 'ph ph-circle-notch';
        btn.setAttribute('data-loading', '');
        btn.disabled = true;
    } else {
        label.textContent = btn.dataset.idle;
        if (icon) icon.className = btn.dataset.idleIcon;
        btn.removeAttribute('data-loading');
        btn.disabled = false;
    }
}

function initLoadingForms() {
    document.addEventListener('submit', (e) => {
        const btn = e.submitter;
        if (!btn?.dataset.loadingText || e.defaultPrevented) return;
        // Kunci setelah submit berjalan agar nilai tombol tetap terkirim.
        setTimeout(() => setLoading(btn, true));
        // Unduhan tidak memuat ulang halaman: buka kembali tombol setelah beberapa detik.
        setTimeout(() => setLoading(btn, false), 4000);
    });
}

/* Dialog: buka dengan [data-open="id"], tutup dengan [data-close] atau Esc (bawaan <dialog>). */
function initModals() {
    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-open]');
        if (opener) {
            const dlg = document.getElementById(opener.dataset.open);
            dlg.dispatchEvent(new CustomEvent('modal:open', { detail: opener.dataset }));
            dlg.showModal();
        }
        if (e.target.closest('[data-close]')) e.target.closest('dialog')?.close();
        if (e.target.matches('dialog.modal')) e.target.close();
    });
}

/* Salin nomor: ikon berganti centang selama 2 detik. */
function initCopy() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-copy]');
        if (!btn) return;
        try {
            await navigator.clipboard.writeText(btn.dataset.copy);
            const icon = btn.querySelector('i');
            const prev = icon.className;
            const prevLabel = btn.getAttribute('aria-label');
            icon.className = 'ph ph-check text-success';
            btn.setAttribute('aria-label', 'Tersalin');
            setTimeout(() => { icon.className = prev; btn.setAttribute('aria-label', prevLabel); }, 2000);
        } catch {
            toast('Nomor belum bisa disalin. Pilih teksnya lalu salin manual.', 'warning');
        }
    });
}

/* Tampilkan kata sandi. */
function initPasswordToggle() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-toggle-password]');
        if (!btn) return;
        const input = document.getElementById(btn.dataset.togglePassword);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        btn.querySelector('i').className = show ? 'ph ph-eye-slash' : 'ph ph-eye';
    });
}

/* Batalkan sertifikat: kirim alasan ke POST /riwayat/{id}/batalkan (JSON dari HistoryController). */
function initCancel() {
    const dlg = document.getElementById('cancel-modal');
    if (!dlg) return;
    const form = dlg.querySelector('form');
    const err = dlg.querySelector('[data-error]');
    dlg.addEventListener('modal:open', (e) => {
        form.action = e.detail.action;
        dlg.querySelector('[data-nomor]').textContent = e.detail.nomor;
        form.reset();
        err.hidden = true;
    });
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.submitter;
        setLoading(btn, true);
        try {
            const res = await window.axios.post(form.action, new FormData(form));
            dlg.close();
            toast(res.data.message, 'success');
            const id = res.data.id;
            $$(`[data-status-for="${id}"]`).forEach((b) => { b.className = 'badge badge-danger'; b.innerHTML = '<i class="ph-fill ph-x-circle"></i>Dibatalkan'; });
            $$(`[data-cancel-for="${id}"]`).forEach((b) => b.remove());
        } catch (ex) {
            err.textContent = ex.response?.data?.errors?.alasan?.[0]
                ?? ex.response?.data?.message
                ?? 'Pembatalan belum tersimpan. Coba lagi.';
            err.hidden = false;
        } finally {
            setLoading(btn, false);
        }
    });
}

/* Menu ponsel (sisa tautan). */
function initMobileMenu() {
    const sheet = document.getElementById('mobile-menu');
    if (!sheet) return;
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-menu-toggle]')) sheet.showModal();
    });
}

/* Menu akun (<details>): tutup saat klik di luar atau tekan Esc. */
function initDropdowns() {
    const closeAll = (except) => $$('details[open]').forEach((d) => d !== except && d.removeAttribute('open'));
    document.addEventListener('click', (e) => closeAll(e.target.closest('details')));
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const open = document.querySelector('details[open]');
        if (open) { open.removeAttribute('open'); open.querySelector('summary').focus(); }
    });
}

/*
 * Konfirmasi sebelum aksi berisiko:
 * <form data-confirm="Pesan" data-confirm-title="Judul" data-confirm-tone="danger|brand">.
 */
function initConfirm() {
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        const msg = form.dataset.confirm;
        if (!msg) return;
        if (form.dataset.confirmed) { delete form.dataset.confirmed; return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        const ok = await confirmDialog({
            title: form.dataset.confirmTitle ?? 'Lanjutkan aksi ini?',
            message: msg,
            confirm: e.submitter?.textContent.trim() || 'Lanjutkan',
            tone: form.dataset.confirmTone ?? 'danger',
        });
        if (!ok) return;
        form.dataset.confirmed = '1';
        form.requestSubmit(e.submitter);
    }, true);
}

/*
 * Saringan dan pencarian tanpa memuat ulang halaman: <form method="GET" data-live>.
 * Hasil diambil ulang di latar, lalu setiap [data-live-region="nama"] ditukar dengan versi baru.
 * Posisi gulir tetap, URL ikut diperbarui supaya bisa dibagikan atau di-refresh.
 */
function initLiveFilter() {
    let ctrl;
    async function load(url) {
        const regions = $$('[data-live-region]');
        if (!regions.length) return location.assign(url);
        ctrl?.abort();
        ctrl = new AbortController();
        regions.forEach((r) => r.setAttribute('aria-busy', 'true'));
        try {
            const res = await fetch(url, { signal: ctrl.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error(res.status);
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            regions.forEach((r) => {
                const next = doc.querySelector(`[data-live-region="${r.dataset.liveRegion}"]`);
                if (next) { next.style.animation = 'none'; r.replaceWith(next); } else r.removeAttribute('aria-busy');
            });
            history.replaceState(null, '', url);
        } catch (ex) {
            if (ex.name === 'AbortError') return;
            regions.forEach((r) => r.removeAttribute('aria-busy'));
            toast('Hasil pencarian belum bisa dimuat. Periksa koneksi lalu coba lagi.', 'danger');
        }
    }

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented) return;
        // Kotak cari di topbar saat sudah di halaman Riwayat: pakai saringan halaman, tanpa pindah.
        const live = document.querySelector('form[data-live]');
        if (form.matches('.search-pill') && live?.elements.q && new URL(form.action).pathname === location.pathname) {
            e.preventDefault();
            live.elements.q.value = form.elements.q.value;
            live.requestSubmit();
            return;
        }
        if (!form.matches('form[data-live]')) return;
        e.preventDefault();
        const url = new URL(form.getAttribute('action') || location.pathname, location.origin);
        for (const [k, v] of new FormData(form)) if (v !== '') url.searchParams.append(k, v);
        load(url);
    });

    document.addEventListener('click', (e) => {
        // Hapus saringan: kosongkan isian lalu muat ulang hasil.
        const reset = e.target.closest('[data-live-reset]');
        if (reset) {
            e.preventDefault();
            const form = reset.closest('form');
            form.querySelectorAll('input, select').forEach((el) => {
                if (el.type === 'checkbox' || el.type === 'radio') el.checked = false;
                else if (el.type !== 'hidden') el.value = '';
            });
            form.requestSubmit();
            return;
        }
        // Halaman berikutnya (paginasi) di dalam hasil juga tanpa memuat ulang.
        const link = e.target.closest('[data-live-region] a[href]');
        if (!link || e.ctrlKey || e.metaKey || e.shiftKey) return;
        const url = new URL(link.href);
        if (url.pathname !== location.pathname || !url.searchParams.has('page')) return;
        e.preventDefault();
        load(url);
    });
}

initSpotlight();
initSideHover();
initFlash();
initDropdowns();
initConfirm();
initLiveFilter();
initLoadingForms();
initModals();
initCopy();
initPasswordToggle();
initCancel();
initMobileMenu();
initIssueForm();
