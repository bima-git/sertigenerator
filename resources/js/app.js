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

/* Pesan kilat: hilang sendiri setelah 6 detik kecuali galat. */
export function toast(message, variant = 'success') {
    const host = document.getElementById('toasts');
    if (!host) return;
    const icon = { success: 'ph-check-circle', danger: 'ph-warning-octagon', info: 'ph-info', warning: 'ph-warning' }[variant];
    const el = document.createElement('div');
    el.setAttribute('role', variant === 'danger' ? 'alert' : 'status');
    el.className = `alert alert-${variant} pointer-events-auto shadow-lg`;
    el.style.animation = reduce ? 'none' : 'toast-in .4s var(--ease-out)';
    el.innerHTML = `<i class="ph-fill ${icon}"></i><span class="flex-1"></span><button type="button" class="opacity-70 hover:opacity-100" aria-label="Tutup pesan"><i class="ph ph-x"></i></button>`;
    el.querySelector('span').textContent = message;
    el.querySelector('button').onclick = () => el.remove();
    host.prepend(el);
    if (variant !== 'danger') setTimeout(() => el.remove(), 6000);
}
window.toast = toast;

function initFlash() {
    $$('[data-flash]').forEach((el) => el.dataset.flash !== 'danger' && setTimeout(() => el.remove(), 6000));
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

/* Konfirmasi sebelum aksi berisiko: <form data-confirm="Teks">. */
function initConfirm() {
    document.addEventListener('submit', (e) => {
        const msg = e.target.dataset.confirm;
        if (msg && !window.confirm(msg)) e.preventDefault();
    }, true);
}

initSpotlight();
initSideHover();
initFlash();
initConfirm();
initLoadingForms();
initModals();
initCopy();
initPasswordToggle();
initCancel();
initMobileMenu();
initIssueForm();
