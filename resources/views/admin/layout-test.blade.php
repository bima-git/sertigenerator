@extends('layouts.app')
@section('title', 'Uji Layout')

@section('content')
@php
    $contoh = [
        'Pendek' => 'Mourice',
        'Panjang' => 'Akrom Masani Iksanul Amri',
        'Apostrof' => "Mu'ammal Hamidy",
        'Bergelar' => 'Dr. Ir. Raden Ayu Prameswari, S.Kom., M.T.',
        '70 karakter' => 'Muhammad Fadhlurrahman Abdillah Prawiranegara Kusumawardhana Santoso',
    ];
@endphp
<x-page-header title="Uji layout PDF" subtitle="Buat PDF contoh untuk kalibrasi posisi teks. Garis bantu menampilkan grid milimeter dan koordinat." />

{{-- Kontrak: GET admin/uji-layout dengan parameter jenis, grid, nama (Design System 3.4). --}}
<form method="GET" action="{{ route('admin.layout.test') }}" target="_blank" class="card p-5 sm:p-7 max-w-3xl grid gap-6" data-reveal style="--i:1">
    <div class="field">
        <span class="label">Jenis</span>
        <div class="segmented w-full sm:w-fit" style="--count:2; --index:0">
            <span class="segmented-thumb" aria-hidden="true"></span>
            <input type="radio" name="jenis" id="lt-seminar" value="seminar" checked onchange="this.parentNode.style.setProperty('--index', 0)">
            <label for="lt-seminar">Seminar/Webinar</label>
            <input type="radio" name="jenis" id="lt-bootcamp" value="bootcamp" onchange="this.parentNode.style.setProperty('--index', 1)">
            <label for="lt-bootcamp">Bootcamp/Magang</label>
        </div>
    </div>
    <div class="field">
        <label for="nama" class="label">Nama contoh</label>
        <input id="nama" name="nama" class="input" maxlength="100" value="Mu'ammal Hamidy">
        <div class="flex gap-2 overflow-x-auto pb-1 mt-1" aria-label="Nama uji cepat">
            @foreach ($contoh as $lbl => $nama)
                <button type="button" class="chip" data-nama="{{ $nama }}" onclick="document.getElementById('nama').value = this.dataset.nama">{{ $lbl }}</button>
            @endforeach
        </div>
    </div>
    <label class="switch">
        <input type="checkbox" name="grid" value="1" checked>
        <span class="switch-track"></span>
        <span><span class="block text-sm font-medium text-fg">Garis Bantu</span><span class="block text-xs text-muted">Grid milimeter dan label koordinat</span></span>
    </label>
    <div class="flex justify-end">
        <button class="btn btn-primary" data-loading-text="Membuat PDF"><i class="ph ph-file-pdf"></i><span data-label>Buat PDF Uji</span></button>
    </div>
</form>
@endsection
