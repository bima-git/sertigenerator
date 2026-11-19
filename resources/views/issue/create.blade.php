@extends('layouts.app')
@section('title', 'Terbitkan')

@section('content')
@php($jenis = old('jenis', request('jenis', 'seminar')))
<x-page-header title="Terbitkan sertifikat" subtitle="Nomor tersusun otomatis dari kegiatan, seri, dan tanggal terbit. Periksa ringkasan sebelum menerbitkan." />

<script type="application/json" id="events-data">@json($events)</script>

<form id="issue-form" method="POST" action="{{ url('/terbitkan') }}" novalidate
      class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:[grid-template-areas:'fields_summary''actions_summary']">
    @csrf

    {{-- Isian --}}
    <section class="card p-5 sm:p-7 lg:[grid-area:fields]" data-reveal style="--i:1" aria-label="Data sertifikat">
        <div class="segmented w-full sm:w-auto" style="--count:2">
            <span class="segmented-thumb" aria-hidden="true"></span>
            <input type="radio" name="jenis" id="jenis-seminar" value="seminar" @checked($jenis === 'seminar')>
            <label for="jenis-seminar"><i class="ph ph-presentation-chart"></i>Seminar/Webinar</label>
            <input type="radio" name="jenis" id="jenis-bootcamp" value="bootcamp" @checked($jenis === 'bootcamp')>
            <label for="jenis-bootcamp"><i class="ph ph-graduation-cap"></i>Bootcamp/Magang</label>
        </div>

        <div class="mt-7 grid gap-5 md:grid-cols-2">
            {{-- Kegiatan --}}
            <div class="field md:col-span-2">
                <label for="event-search" id="event-label" class="label">Kegiatan</label>
                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                    <input id="event-search" class="input pl-10" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="event-list" autocomplete="off" @error('event_id') aria-invalid="true" @enderror>
                    <ul id="event-list" class="combo-list" role="listbox" hidden></ul>
                </div>
                <input type="hidden" name="event_id" value="{{ old('event_id') }}">
                @error('event_id') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>

            {{-- Seminar --}}
            <div class="field md:col-span-2" data-for="seminar">
                <div class="flex items-center justify-between gap-2">
                    <label for="judul_cetak" class="label">Judul pada sertifikat</label>
                    <button type="button" id="restore-judul" class="btn btn-ghost btn-sm -my-2" hidden><i class="ph ph-arrow-counter-clockwise"></i>Kembalikan Judul Asli</button>
                </div>
                <textarea id="judul_cetak" name="judul_cetak" class="input !min-h-[72px]" rows="2" maxlength="200" @error('judul_cetak') aria-invalid="true" @enderror>{{ old('judul_cetak') }}</textarea>
                <p class="hint">Terisi dari judul kegiatan. Ubah bila perlu, maksimal 200 karakter.</p>
                @error('judul_cetak') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>

            {{-- Bootcamp --}}
            <div class="field md:col-span-2" data-for="bootcamp">
                <label for="program" class="label">Program</label>
                <input id="program" name="program" class="input" maxlength="150" value="{{ old('program') }}" placeholder="Penetration Testing & Cyber Security Engineer" @error('program') aria-invalid="true" @enderror>
                @error('program') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>

            {{-- Nama --}}
            <div class="field md:col-span-2">
                <div class="flex items-center justify-between gap-2">
                    <label for="nama_peserta" class="label">Nama lengkap peserta</label>
                    <button type="button" id="tidy-name" class="btn btn-ghost btn-sm -my-2"><i class="ph ph-text-aa"></i>Rapikan Kapitalisasi</button>
                </div>
                <input id="nama_peserta" name="nama_peserta" class="input" maxlength="100" value="{{ old('nama_peserta') }}" autocomplete="off" aria-describedby="nama-count" @error('nama_peserta') aria-invalid="true" @enderror>
                <p class="hint flex justify-between"><span>Tulis sesuai ejaan yang diinginkan peserta.</span><span id="nama-count" class="num">0/100</span></p>
                @error('nama_peserta') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>

            <div class="field md:col-span-2" data-for="bootcamp">
                <label for="perusahaan" class="label">Perusahaan penempatan</label>
                <input id="perusahaan" name="perusahaan" class="input" maxlength="150" value="{{ old('perusahaan') }}" placeholder="PT Teknologi Keamanan Digital" @error('perusahaan') aria-invalid="true" @enderror>
                @error('perusahaan') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>
            <div class="field" data-for="bootcamp">
                <label for="tanggal_mulai" class="label">Tanggal mulai</label>
                <input id="tanggal_mulai" name="tanggal_mulai" type="date" class="input" value="{{ old('tanggal_mulai') }}" @error('tanggal_mulai') aria-invalid="true" @enderror>
                @error('tanggal_mulai') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>
            <div class="field" data-for="bootcamp">
                <label for="tanggal_selesai" class="label">Tanggal selesai</label>
                <input id="tanggal_selesai" name="tanggal_selesai" type="date" class="input" value="{{ old('tanggal_selesai') }}" @error('tanggal_selesai') aria-invalid="true" @enderror>
                <p id="selesai-error" class="error" hidden><i class="ph-fill ph-warning-circle"></i>Tanggal selesai tidak boleh sebelum tanggal mulai.</p>
                @error('tanggal_selesai') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>

            {{-- Penomoran --}}
            <div class="field" data-numbered>
                <label for="seri" class="label">Seri</label>
                <input id="seri" name="seri" type="number" min="1" max="999" class="input num" value="{{ old('seri', 1) }}" @error('seri') aria-invalid="true" @enderror>
                <p class="hint">Ditampilkan 3 digit, bawaan 001.</p>
                @error('seri') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>
            <div class="field" data-numbered>
                <div class="flex items-center justify-between gap-2">
                    <label for="urut" class="label">Nomor urut</label>
                    <button type="button" id="use-suggest" class="btn btn-ghost btn-sm -my-2" hidden><i class="ph ph-magic-wand"></i>Gunakan Nomor Saran</button>
                </div>
                <input id="urut" name="urut" type="number" min="1" max="999" class="input num" value="{{ old('urut') }}" @error('urut') aria-invalid="true" @enderror>
                <p class="hint">Terisi saran sistem. Ubah bila mengikuti daftar peserta.</p>
                @error('urut') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="tanggal_terbit" class="label">Tanggal terbit</label>
                <input id="tanggal_terbit" name="tanggal_terbit" type="date" class="input" value="{{ old('tanggal_terbit', now()->toDateString()) }}" required @error('tanggal_terbit') aria-invalid="true" @enderror>
                @error('tanggal_terbit') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Ringkasan --}}
    <aside class="lg:[grid-area:summary]" aria-label="Ringkasan penerbitan">
        <div class="card overflow-hidden lg:sticky lg:top-8" data-reveal style="--i:2">
            <div class="relative bg-side px-5 py-5 text-white overflow-hidden">
                <div class="glow w-56 h-56 -right-20 -top-24 opacity-40"></div>
                <p class="relative text-sm text-zinc-400">Nomor sertifikat</p>
                <div id="sum-number" class="relative mt-3 flex flex-wrap items-start gap-x-0.5 gap-y-2" aria-live="polite">
                    @foreach (['kode' => 'kode', 'seri' => 'seri', 'urut' => 'urut', 'penerbit' => 'penerbit', 'bulan' => 'bulan', 'tahun' => 'tahun'] as $key => $lbl)
                        @if (! $loop->first) <span class="seg-sep !text-zinc-500">/</span> @endif
                        <span class="seg">
                            <span class="seg-val !bg-white/5 !border-white/10 !text-white !text-[13px] !px-1.5" data-seg="{{ $key }}">-</span>
                            <span class="seg-lbl !text-zinc-500">{{ $lbl }}</span>
                        </span>
                    @endforeach
                </div>
                <p id="sum-no-number" class="relative mt-3 text-lg font-semibold" hidden>Tanpa nomor</p>
                <button type="button" id="copy-number" class="btn btn-icon absolute right-3 top-3 !text-zinc-400 hover:!bg-white/10 hover:!text-white" aria-label="Salin nomor" hidden><i class="ph ph-copy"></i></button>
            </div>

            <dl class="grid gap-4 p-5 text-sm">
                <div>
                    <dt class="text-muted">Nama berkas</dt>
                    <dd id="sum-file" class="num mt-1 break-all text-fg">Sertifikat_Seminar_Nama_Peserta.pdf</dd>
                </div>
                <div>
                    <dt class="text-muted">Judul tercetak</dt>
                    <dd id="sum-title" class="mt-1 text-fg leading-relaxed">Pilih kegiatan untuk melihat judul.</dd>
                </div>
                <div data-warnings hidden>
                    <dt class="text-muted mb-2">Peringatan</dt>
                    <dd><ul id="sum-warnings" class="grid gap-2"></ul></dd>
                </div>
                <p id="sum-source" class="hint flex gap-1.5" hidden><i class="ph ph-plug"></i>Nomor dihitung di peramban karena layanan nomor belum terhubung. Server tetap memeriksa ulang.</p>
            </dl>
        </div>
    </aside>

    {{-- Aksi --}}
    <div class="lg:[grid-area:actions] max-lg:sticky max-lg:bottom-[calc(64px+env(safe-area-inset-bottom))] max-lg:-mx-4 max-lg:px-4 max-lg:py-3 max-lg:bg-surface/90 max-lg:backdrop-blur" data-reveal style="--i:3">
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
            <button type="button" id="btn-reset" class="btn btn-ghost"><i class="ph ph-eraser"></i>Kosongkan Form</button>
            <div class="sm:ml-auto flex flex-col-reverse gap-2 sm:flex-row">
                <button class="btn btn-secondary" formaction="{{ url('/pratinjau') }}" formtarget="_blank" data-loading-text="Membuat pratinjau"><i class="ph ph-eye"></i><span data-label>Pratinjau</span></button>
                <button id="btn-submit" class="btn btn-primary btn-lg" data-loading-text="Menerbitkan" aria-describedby="submit-reason"><i class="ph ph-download-simple"></i><span data-label>Terbitkan &amp; Unduh</span></button>
            </div>
        </div>
        <p id="submit-reason" class="hint mt-2 sm:text-right" aria-live="polite"></p>
    </div>
</form>
@endsection
