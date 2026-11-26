@extends('layouts.app')
@section('title', $event ? 'Ubah kegiatan' : 'Tambah kegiatan')

@section('content')
@php
    $v = fn ($k, $d = null) => old($k, $event[$k] ?? $d);
    $jenis = $v('jenis', 'seminar');
    $seminar = $jenis === 'seminar';
    $seminarLamaTanpaNomor = $event && $seminar && $event['jenis'] === 'seminar' && ! $event['bernomor'];
@endphp
<a href="{{ route('admin.events.index') }}" class="btn btn-ghost btn-sm -ml-3 mb-4" data-reveal><i class="ph ph-arrow-left"></i>Kegiatan</a>
<x-page-header :title="$event ? 'Ubah kegiatan '.$event['kode'] : 'Tambah kegiatan'" subtitle="Kode ditetapkan manual sesuai dokumen aturan kode, bukan penghitung otomatis." />

<form id="event-form" method="POST" action="{{ $event ? url('/admin/kegiatan/'.$event['id'].'/ubah') : url('/admin/kegiatan/baru') }}" class="card p-5 sm:p-7 max-w-3xl grid gap-5 md:grid-cols-2" data-reveal style="--i:1">
    @csrf
    <div class="field md:col-span-2">
        <span class="label">Jenis</span>
        <div class="segmented w-full sm:w-fit" style="--count:2; --index: {{ $jenis === 'seminar' ? 0 : 1 }}">
            <span class="segmented-thumb" aria-hidden="true"></span>
            <input type="radio" name="jenis" id="ej-seminar" value="seminar" @checked($jenis === 'seminar') onchange="this.parentNode.style.setProperty('--index', 0)">
            <label for="ej-seminar">Seminar/Webinar</label>
            <input type="radio" name="jenis" id="ej-bootcamp" value="bootcamp" @checked($jenis === 'bootcamp') onchange="this.parentNode.style.setProperty('--index', 1)">
            <label for="ej-bootcamp">Bootcamp/Magang</label>
        </div>
    </div>
    <div class="field">
        <label for="kode" class="label">Kode kegiatan</label>
        <input id="kode" name="kode" class="input num" maxlength="10" required value="{{ $v('kode') }}" placeholder="35" @error('kode') aria-invalid="true" @enderror>
        <p class="hint">Teks, boleh sama dengan versi kegiatan lain.</p>
        <p class="hint !text-warning flex gap-1.5" data-kode-dipakai hidden><i class="ph-fill ph-warning mt-0.5"></i><span></span></p>
        @error('kode') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="penerbit" class="label">Penerbit</label>
        <input id="penerbit" name="penerbit" class="input" maxlength="50" required value="{{ $v('penerbit', 'Xcodetraining') }}" list="penerbit-list" @error('penerbit') aria-invalid="true" @enderror>
        <datalist id="penerbit-list"><option value="Xcodetraining"><option value="CSS"><option value="Bootcamp"><option value="SiberExpert"></datalist>
        <p class="hint">Muncul di segmen keempat nomor.</p>
        @error('penerbit') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
    </div>
    <div class="field md:col-span-2">
        <label for="judul" class="label">Judul kegiatan</label>
        <textarea id="judul" name="judul" class="input !min-h-[72px]" rows="2" maxlength="500" required @error('judul') aria-invalid="true" @enderror>{{ $v('judul') }}</textarea>
        @error('judul') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
    </div>
    <div class="field md:col-span-2">
        <label for="program_default" class="label">Program bawaan <span class="font-normal text-muted">(bootcamp)</span></label>
        <input id="program_default" name="program_default" class="input" maxlength="200" value="{{ $v('program_default') }}" placeholder="Penetration Testing & Cyber Security Engineer">
    </div>
    <div class="md:col-span-2 grid gap-4 sm:grid-cols-3 rounded-2xl bg-alt p-4">
        <div class="grid content-start gap-2" data-bernomor-field data-bernomor-mode="{{ $jenis }}">
            <label class="switch">
                <input type="hidden" name="bernomor" value="{{ $seminar ? '1' : '0' }}" data-bernomor-hidden>
                <input type="checkbox" name="bernomor" value="1" data-bernomor-checkbox
                    @checked($seminar || $v('bernomor'))
                    @disabled($seminar)
                    aria-describedby="bernomor-hint"
                    @error('bernomor') aria-invalid="true" @enderror>
                <span class="switch-track"></span>
                <span>
                    <span class="block text-sm font-medium text-fg">Bernomor</span>
                    <span id="bernomor-hint" class="block text-xs text-muted" data-bernomor-hint>{{ $seminar ? 'Seminar/webinar wajib bernomor.' : 'Bawaan mati untuk bootcamp.' }}</span>
                </span>
            </label>
            @error('bernomor')
                <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p>
            @enderror
        </div>
        @foreach (['pakai_qr' => ['Pakai QR', 'Umumnya hanya seminar'], 'aktif' => ['Aktif', 'Muncul di form Terbitkan']] as $key => [$lbl, $hint])
            <label class="switch">
                <input type="hidden" name="{{ $key }}" value="0">
                <input type="checkbox" name="{{ $key }}" value="1" @checked($v($key, $key === 'aktif' || $jenis === 'seminar'))>
                <span class="switch-track"></span>
                <span><span class="block text-sm font-medium text-fg">{{ $lbl }}</span><span class="block text-xs text-muted">{{ $hint }}</span></span>
            </label>
        @endforeach
    </div>
    @if ($seminarLamaTanpaNomor)
        <div class="md:col-span-2 alert alert-info" role="status" data-bernomor-legacy>
            <i class="ph-fill ph-info"></i>
            <span>Kegiatan seminar lama ini belum bernomor. Penomoran akan diaktifkan saat kegiatan disimpan.</span>
        </div>
    @endif
    <div class="md:col-span-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end pt-2">
        <a href="{{ route('admin.events.index') }}" class="btn btn-ghost">Kembali</a>
        <button class="btn btn-primary" data-loading-text="Menyimpan"><i class="ph ph-floppy-disk"></i><span data-label>Simpan Kegiatan</span></button>
    </div>
</form>

{{-- Seminar/webinar wajib bernomor: kunci switch saat jenis seminar. Skrip lokal
     agar tidak mengubah perilaku global di app.js. --}}
<script>
(() => {
    const form = document.getElementById('event-form');
    if (!form) return;

    const jenisSeminar = form.querySelector('#ej-seminar');
    const jenisBootcamp = form.querySelector('#ej-bootcamp');
    const field = form.querySelector('[data-bernomor-field]');
    const checkbox = form.querySelector('[data-bernomor-checkbox]');
    const hidden = form.querySelector('[data-bernomor-hidden]');
    const hint = form.querySelector('[data-bernomor-hint]');
    const legacy = form.querySelector('[data-bernomor-legacy]');

    if (!jenisSeminar || !jenisBootcamp || !field || !checkbox || !hidden || !hint) return;

    const sync = () => {
        const seminar = jenisSeminar.checked;
        field.dataset.bernomorMode = seminar ? 'seminar' : 'bootcamp';

        if (seminar) {
            // Checkbox dinonaktifkan agar tidak bisa dimatikan; hidden input
            // memastikan payload bernomor=1 tetap terkirim.
            checkbox.checked = true;
            checkbox.disabled = true;
            hidden.value = '1';
            hint.textContent = 'Seminar/webinar wajib bernomor.';
        } else {
            checkbox.disabled = false;
            hidden.value = '0';
            hint.textContent = 'Bawaan mati untuk bootcamp.';
        }

        if (legacy) legacy.hidden = !seminar;
    };

    [jenisSeminar, jenisBootcamp].forEach((radio) => radio.addEventListener('change', sync));
    sync();
})();

/* PRD KG-04: kode yang sama boleh dipakai, tetapi setelah konfirmasi yang menyebut kegiatan pemakainya.
   Konfirmasi memakai dialog [data-confirm] dari app.js; server tetap menerima kode ganda. */
(() => {
    const form = document.getElementById('event-form');
    const input = form?.querySelector('#kode');
    const note = form?.querySelector('[data-kode-dipakai]');
    if (!input || !note) return;
    const lain = @json(\App\Models\Event::query()->when($event, fn ($q) => $q->whereKeyNot($event['id']))->get(['kode', 'judul']));

    const sync = () => {
        const kode = input.value.trim().toLowerCase();
        const pakai = kode ? lain.filter((e) => String(e.kode).toLowerCase() === kode).map((e) => `"${e.judul}"`) : [];
        note.hidden = !pakai.length;
        if (!pakai.length) {
            delete form.dataset.confirm;
            return;
        }
        const daftar = pakai.slice(0, 3).join(', ') + (pakai.length > 3 ? `, dan ${pakai.length - 3} lainnya` : '');
        note.querySelector('span').textContent = `Kode ini sudah dipakai: ${daftar}.`;
        form.dataset.confirmTitle = `Kode ${input.value.trim()} sudah dipakai`;
        form.dataset.confirm = `Kode ini juga dipakai kegiatan ${daftar}. Tetap simpan dengan kode yang sama?`;
        form.dataset.confirmTone = 'brand';
    };
    input.addEventListener('input', sync);
    sync();
})();
</script>
@endsection
