@extends('layouts.app')
@section('title', $event ? 'Ubah kegiatan' : 'Tambah kegiatan')

@section('content')
@php
    $v = fn ($k, $d = null) => old($k, $event[$k] ?? $d);
    $jenis = $v('jenis', 'seminar');
@endphp
<a href="{{ route('admin.events.index') }}" class="btn btn-ghost btn-sm -ml-3 mb-4" data-reveal><i class="ph ph-arrow-left"></i>Kegiatan</a>
<x-page-header :title="$event ? 'Ubah kegiatan '.$event['kode'] : 'Tambah kegiatan'" subtitle="Kode ditetapkan manual sesuai dokumen aturan kode, bukan penghitung otomatis." />

<form method="POST" action="{{ $event ? url('/admin/kegiatan/'.$event['id'].'/ubah') : url('/admin/kegiatan/baru') }}" class="card p-5 sm:p-7 max-w-3xl grid gap-5 md:grid-cols-2" data-reveal style="--i:1">
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
        @foreach (['bernomor' => ['Bernomor', 'Bawaan mati untuk bootcamp'], 'pakai_qr' => ['Pakai QR', 'Umumnya hanya seminar'], 'aktif' => ['Aktif', 'Muncul di form Terbitkan']] as $key => [$lbl, $hint])
            <label class="switch">
                <input type="hidden" name="{{ $key }}" value="0">
                <input type="checkbox" name="{{ $key }}" value="1" @checked($v($key, $key === 'aktif' || $jenis === 'seminar'))>
                <span class="switch-track"></span>
                <span><span class="block text-sm font-medium text-fg">{{ $lbl }}</span><span class="block text-xs text-muted">{{ $hint }}</span></span>
            </label>
        @endforeach
    </div>
    <div class="md:col-span-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end pt-2">
        <a href="{{ route('admin.events.index') }}" class="btn btn-ghost">Kembali</a>
        <button class="btn btn-primary" data-loading-text="Menyimpan"><i class="ph ph-floppy-disk"></i><span data-label>Simpan Kegiatan</span></button>
    </div>
</form>
@endsection
