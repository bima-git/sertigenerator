/*
 * Form Terbitkan (Design System 8.4).
 * Kontrak API dari IssueController (branch Bima):
 *   GET /api/nomor?event_id&seri&tanggal_terbit&urut
 *   -> { bernomor, urut_saran, urut_aktif, nomor_lengkap, kode, penerbit, seri, tahun, is_available }
 * Bila endpoint belum tersedia, nomor dihitung lokal hanya untuk pratinjau.
 * Server tetap memvalidasi ulang semuanya.
 */
import { confirmDialog, setLoading, toast } from './app';

const ROMAN =['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
const pad3 = (n) => String(n).padStart(3, '0');

export function initIssueForm() {
    const form = document.getElementById('issue-form');
    if (!form) return;

    const events = JSON.parse(document.getElementById('events-data').textContent);
    const $ = (sel) => form.querySelector(sel);
    const f = {
        eventId: $('[name=event_id]'),
        search: $('#event-search'),
        list: $('#event-list'),
        judul: $('[name=judul_cetak]'),
        nama: $('[name=nama_peserta]'),
        seri: $('[name=seri]'),
        urut: $('[name=urut]'),
        terbit: $('[name=tanggal_terbit]'),
        program: $('[name=program]'),
        perusahaan: $('[name=perusahaan]'),
        mulai: $('[name=tanggal_mulai]'),
        selesai: $('[name=tanggal_selesai]'),
    };
    const ui = {
        seg: form.querySelector('.segmented'),
        counter: $('#nama-count'),
        useSuggest: $('#use-suggest'),
        restoreJudul: $('#restore-judul'),
        submit: $('#btn-submit'),
        reason: $('#submit-reason'),
        number: document.getElementById('sum-number'),
        noNumber: document.getElementById('sum-no-number'),
        file: document.getElementById('sum-file'),
        title: document.getElementById('sum-title'),
        warnings: document.getElementById('sum-warnings'),
        source: document.getElementById('sum-source'),
        dateErr: $('#selesai-error'),
    };

    let jenis = form.querySelector('[name=jenis]:checked').value;
    let event = events.find((e) => String(e.id) === f.eventId.value) ?? null;
    let suggestion = null;
    let urutTouched = Boolean(f.urut.value);
    let activeIndex = -1;

    /* ---------- Jenis ---------- */
    function applyJenis() {
        const idx = jenis === 'seminar' ? 0 : 1;
        ui.seg.style.setProperty('--index', idx);
        form.querySelectorAll('[data-for]').forEach((group) => {
            const show = group.dataset.for === jenis;
            group.hidden = !show;
            // Field tersembunyi dinonaktifkan agar tidak ikut terkirim.
            group.querySelectorAll('input, select, textarea').forEach((el) => (el.disabled = !show));
        });
        $('#event-label').textContent = jenis === 'seminar' ? 'Kegiatan' : 'Bootcamp';
        f.search.placeholder = jenis === 'seminar' ? 'Cari kode atau judul seminar' : 'Cari bootcamp';
        if (event && event.jenis !== jenis) selectEvent(null);
    }

    form.querySelectorAll('[name=jenis]').forEach((r) =>
        r.addEventListener('change', () => { jenis = r.value; applyJenis(); render(); }),
    );

    /* ---------- Dropdown kegiatan dengan pencarian ---------- */
    function options() {
        const q = f.search.value.trim().toLowerCase();
        const pickedLabel = event ? label(event).toLowerCase() : null;
        return events.filter((e) => e.jenis === jenis && e.aktif
            && (!q || q === pickedLabel || `${e.kode} ${e.judul}`.toLowerCase().includes(q)));
    }
    const label = (e) => `${e.kode} - ${e.judul}`;

    function openList() {
        const opts = options();
        f.list.replaceChildren(...(opts.length ? opts.map((e, i) => {
            const li = document.createElement('li');
            li.className = 'combo-option' + (event?.id === e.id ? ' is-active' : '');
            li.id = `ev-${e.id}`;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', String(i === activeIndex));
            li.innerHTML = '<span class="num text-sm font-semibold text-brand w-10 flex-none"></span><span class="text-sm text-fg"></span>';
            li.children[0].textContent = e.kode;
            li.children[1].textContent = e.judul;
            li.addEventListener('mousedown', (ev) => { ev.preventDefault(); selectEvent(e); closeList(); });
            return li;
        }) : [Object.assign(document.createElement('li'), {
            className: 'px-3 py-4 text-sm text-muted',
            textContent: 'Tidak ada kegiatan aktif yang cocok. Admin dapat menambahkannya di halaman Kegiatan.',
        })]));
        f.list.hidden = false;
        f.search.setAttribute('aria-expanded', 'true');
    }
    function closeList() {
        f.list.hidden = true;
        activeIndex = -1;
        f.search.setAttribute('aria-expanded', 'false');
        if (event) f.search.value = label(event);
    }

    f.search.addEventListener('focus', () => { f.search.select(); openList(); });
    f.search.addEventListener('input', () => { activeIndex = 0; openList(); });
    f.search.addEventListener('blur', closeList);
    f.search.addEventListener('keydown', (e) => {
        const opts = options();
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = (activeIndex + (e.key === 'ArrowDown' ? 1 : -1) + opts.length) % opts.length;
            openList();
            f.list.children[activeIndex]?.scrollIntoView({ block: 'nearest' });
            f.search.setAttribute('aria-activedescendant', `ev-${opts[activeIndex]?.id}`);
        } else if (e.key === 'Enter') {
            if (!f.list.hidden && opts[Math.max(activeIndex, 0)]) {
                e.preventDefault();
                selectEvent(opts[Math.max(activeIndex, 0)]);
                closeList();
            }
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    function selectEvent(e) {
        event = e;
        f.eventId.value = e ? e.id : '';
        f.search.value = e ? label(e) : '';
        if (e) {
            if (jenis === 'seminar') f.judul.value = e.judul;
            else if (!f.program.value || f.program.dataset.auto) { f.program.value = e.program_default ?? ''; f.program.dataset.auto = '1'; }
        }
        urutTouched = false;
        suggestion = null;
        render();
        requestSuggestion();
    }

    /* ---------- Saran nomor ---------- */
    let timer;
    let reqId = 0;
    function requestSuggestion() {
        clearTimeout(timer);
        if (!event) return;
        timer = setTimeout(async () => {
            const id = ++reqId;
            const params = { event_id: event.id, seri: f.seri.value || 1, tanggal_terbit: f.terbit.value };
            if (urutTouched && f.urut.value) params.urut = f.urut.value;
            let data;
            try {
                data = (await window.axios.get('/api/nomor', { params })).data;
                data.source = 'server';
            } catch {
                data = localSuggestion();
            }
            if (id !== reqId) return; // jawaban usang
            suggestion = data;
            if (!urutTouched && data.urut_saran) f.urut.value = data.urut_saran;
            render();
        }, 220);
    }

    function localSuggestion() {
        const d = new Date(f.terbit.value || Date.now());
        const urut = Number(urutTouched && f.urut.value ? f.urut.value : 1);
        return {
            bernomor: event.bernomor, urut_saran: event.bernomor ? 1 : null, urut_aktif: urut,
            kode: event.kode, penerbit: event.penerbit, seri: Number(f.seri.value || 1), tahun: d.getFullYear(),
            nomor_lengkap: null, is_available: true, source: 'local',
        };
    }

    f.seri.addEventListener('input', requestSuggestion);
    f.terbit.addEventListener('change', () => { requestSuggestion(); render(); });
    f.urut.addEventListener('input', () => { urutTouched = true; requestSuggestion(); render(); });
    ui.useSuggest.addEventListener('click', () => {
        urutTouched = false;
        f.urut.value = suggestion?.urut_saran ?? '';
        requestSuggestion();
        render();
    });

    /* ---------- Bantuan isian ---------- */
    $('#tidy-name').addEventListener('click', () => {
        f.nama.value = f.nama.value.trim().replace(/\s+/g, ' ').toLowerCase()
            .replace(/(^|[\s-])(\p{L})/gu, (m, sep, ch) => sep + ch.toUpperCase());
        render();
    });
    ui.restoreJudul.addEventListener('click', () => { f.judul.value = event?.judul ?? ''; render(); });
    f.program.addEventListener('input', () => delete f.program.dataset.auto);

    $('#btn-reset').addEventListener('click', async () => {
        const fields = [f.nama, f.judul, f.urut, f.program, f.perusahaan, f.mulai, f.selesai];
        if (fields.some((el) => el.value) && !(await confirmDialog({
            title: 'Kosongkan form?',
            message: 'Semua isian dihapus. Jenis dan kegiatan tetap terpilih.',
            confirm: 'Kosongkan',
            icon: 'ph-eraser',
        }))) return;
        fields.forEach((el) => (el.value = ''));
        if (event && jenis === 'seminar') f.judul.value = event.judul;
        if (event && jenis === 'bootcamp') f.program.value = event.program_default ?? '';
        urutTouched = false;
        requestSuggestion();
        render();
        f.nama.focus();
    });

    form.addEventListener('input', render);

    /* ---------- Panel ringkasan ---------- */
    function currentSegments() {
        const d = f.terbit.value ? new Date(f.terbit.value) : new Date();
        const s = suggestion;
        return {
            kode: event?.kode ?? '-',
            seri: pad3(s?.seri ?? (f.seri.value || 1)),
            urut: f.urut.value ? pad3(f.urut.value) : '-',
            penerbit: event?.penerbit ?? '-',
            bulan: ROMAN[d.getMonth()],
            tahun: String(d.getFullYear()),
        };
    }

    function setSeg(key, value) {
        const el = ui.number.querySelector(`[data-seg=${key}]`);
        if (el.textContent === value) return;
        el.textContent = value;
        el.classList.remove('flash');
        void el.offsetWidth; // ulang animasi
        el.classList.add('flash');
    }

    function render() {
        const bernomor = jenis === 'seminar' ? (event?.bernomor ?? true) : Boolean(event?.bernomor);
        ui.number.hidden = !bernomor;
        ui.noNumber.hidden = bernomor;
        form.querySelectorAll('[data-numbered]').forEach((el) => {
            el.hidden = !bernomor;
            el.querySelectorAll('input').forEach((i) => (i.disabled = !bernomor));
        });
        const segs = currentSegments();
        Object.entries(segs).forEach(([k, v]) => setSeg(k, v));
        const copyBtn = document.getElementById('copy-number');
        copyBtn.hidden = !(bernomor && event && f.urut.value);
        copyBtn.dataset.copy = Object.values(segs).join('/');

        ui.source.hidden = !(suggestion?.source === 'local');

        // Nama berkas: Sertifikat_Seminar_Muammal_Hamidy.pdf
        const slug = f.nama.value.trim().replace(/[^\p{L}\s-]/gu, '').replace(/\s+/g, '_');
        ui.file.textContent = `Sertifikat_${jenis === 'seminar' ? 'Seminar' : 'Bootcamp'}_${slug || 'Nama_Peserta'}.pdf`;

        const title = jenis === 'seminar' ? f.judul.value : [event?.judul, f.program.value].filter(Boolean).join(' / ');
        ui.title.textContent = title || 'Pilih kegiatan untuk melihat judul.';
        ui.title.classList.toggle('text-muted', !title);

        ui.counter.textContent = `${f.nama.value.length}/100`;
        ui.restoreJudul.hidden = !(event && jenis === 'seminar' && f.judul.value !== event.judul);
        ui.useSuggest.hidden = !(bernomor && suggestion?.urut_saran && urutTouched && Number(f.urut.value) !== suggestion.urut_saran);

        const badDates = f.mulai.value && f.selesai.value && f.selesai.value < f.mulai.value;
        ui.dateErr.hidden = !badDates;
        f.selesai.setAttribute('aria-invalid', String(Boolean(badDates)));

        renderWarnings(bernomor, badDates);
        renderSubmit(bernomor, badDates);
    }

    function renderWarnings(bernomor, badDates) {
        const w = [];
        if (suggestion && bernomor && suggestion.is_available === false) w.push(['danger', `Nomor urut ${f.urut.value} sudah dipakai. Gunakan nomor urut ${suggestion.urut_saran}.`]);
        if (f.terbit.value && Math.abs(new Date(f.terbit.value) - Date.now()) > 365 * 864e5) w.push(['warning', 'Tanggal terbit lebih dari satu tahun dari hari ini.']);
        if (jenis === 'seminar' && f.judul.value.length > 120) w.push(['warning', 'Judul mungkin lebih dari dua baris. Pertimbangkan untuk mempersingkat.']);
        if (f.nama.value.length > 50) w.push(['warning', 'Nama sangat panjang, ukuran huruf di sertifikat akan mengecil.']);
        if (badDates) w.push(['danger', 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);
        if (f.nama.value && !/^[\p{L}\s.,'\-]+$/u.test(f.nama.value)) w.push(['danger', 'Nama hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung.']);

        ui.warnings.replaceChildren(...w.map(([variant, text]) => {
            const li = document.createElement('li');
            li.className = `alert alert-${variant} !py-2.5`;
            li.innerHTML = `<i class="ph-fill ${variant === 'danger' ? 'ph-warning-octagon' : 'ph-warning'}"></i><span></span>`;
            li.querySelector('span').textContent = text;
            return li;
        }));
        ui.warnings.closest('[data-warnings]').hidden = w.length === 0;
    }

    function renderSubmit(bernomor, badDates) {
        const missing = [];
        if (!event) missing.push(jenis === 'seminar' ? 'kegiatan' : 'bootcamp');
        if (f.nama.value.trim().length < 2) missing.push('nama peserta');
        if (!f.terbit.value) missing.push('tanggal terbit');
        if (jenis === 'seminar' && bernomor && !f.urut.value) missing.push('nomor urut');
        if (jenis === 'bootcamp') {
            if (!f.program.value.trim()) missing.push('program');
            if (!f.perusahaan.value.trim()) missing.push('perusahaan');
            if (!f.mulai.value) missing.push('tanggal mulai');
            if (!f.selesai.value) missing.push('tanggal selesai');
        }
        const blocked = missing.length > 0 || badDates;
        ui.submit.disabled = blocked;
        ui.reason.textContent = missing.length ? `Lengkapi ${missing.join(', ')}.` : (badDates ? 'Perbaiki tanggal selesai.' : '');
    }

    /* ---------- Kirim: Terbitkan & Unduh / Pratinjau ---------- */
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.submitter;
        const isPreview = btn?.formAction?.endsWith('/pratinjau');
        // Jendela dibuka sinkron agar tidak diblokir popup blocker, lalu diisi PDF.
        const win = isPreview ? window.open('', '_blank') : null;
        clearErrors();
        setLoading(btn, true);
        try {
            const res = await window.axios.post(isPreview ? btn.formAction : form.action, new FormData(form), {
                responseType: 'blob',
                headers: { Accept: 'application/json' },
            });
            const url = URL.createObjectURL(res.data);
            if (isPreview) {
                win.location = url;
            } else {
                const a = Object.assign(document.createElement('a'), { href: url, download: filenameFrom(res) });
                a.click();
                const nomor = res.headers['x-certificate-number'];
                toast(`Sertifikat atas nama ${f.nama.value.trim()} diterbitkan${nomor ? ` dengan nomor ${nomor}` : ''}. Unduhan dimulai.`, 'success');
                // Siap untuk peserta berikutnya: kegiatan, seri, tanggal tetap.
                f.nama.value = '';
                urutTouched = false;
                requestSuggestion();
                render();
                f.nama.focus();
            }
            setTimeout(() => URL.revokeObjectURL(url), 60_000);
        } catch (ex) {
            win?.close();
            await showError(ex);
        } finally {
            setLoading(btn, false);
            render();
        }
    });

    function filenameFrom(res) {
        const m = /filename="?([^"]+)"?/.exec(res.headers['content-disposition'] ?? '');
        return m ? m[1] : ui.file.textContent;
    }

    async function showError(ex) {
        let body = {};
        try { body = JSON.parse(await ex.response?.data?.text()); } catch { /* bukan JSON */ }
        if (ex.response?.status === 422 && body.errors) {
            Object.entries(body.errors).forEach(([name, msgs]) => {
                const input = form.querySelector(`[name="${name}"]`);
                const fieldEl = input?.closest('.field') ?? (name === 'event_id' ? f.search.closest('.field') : null);
                if (!fieldEl) return;
                (input?.type === 'hidden' ? f.search : input)?.setAttribute('aria-invalid', 'true');
                const p = document.createElement('p');
                p.className = 'error';
                p.dataset.clientError = '';
                p.innerHTML = '<i class="ph-fill ph-warning-circle"></i><span></span>';
                p.querySelector('span').textContent = msgs[0];
                fieldEl.append(p);
            });
            form.querySelector('[aria-invalid="true"]')?.focus();
            toast('Periksa kembali isian yang ditandai.', 'warning');
        } else if (ex.response?.status === 419) {
            toast('Sesi form berakhir. Muat ulang halaman lalu coba lagi.', 'danger');
        } else {
            toast(body.message && ex.response?.status < 500
                ? body.message
                : 'Sertifikat belum bisa dibuat dan nomor tidak terpakai. Coba lagi. Bila berulang, hubungi admin.', 'danger');
            requestSuggestion();
        }
    }

    function clearErrors() {
        form.querySelectorAll('[data-client-error]').forEach((el) => el.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach((el) => el.removeAttribute('aria-invalid'));
    }

    applyJenis();
    render();
    if (event) requestSuggestion();
}
