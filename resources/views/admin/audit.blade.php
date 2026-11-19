@extends('layouts.app')
@section('title', 'Audit')

@section('content')
@php
    $aksi = [
        'masuk' => ['Masuk', 'ph-sign-in', 'badge-neutral'], 'gagal_masuk' => ['Gagal masuk', 'ph-lock-key', 'badge-warning'],
        'terbit' => ['Menerbitkan', 'ph-seal-check', 'badge-success'], 'unduh_ulang' => ['Mengunduh ulang', 'ph-download-simple', 'badge-info'],
        'batal' => ['Membatalkan', 'ph-prohibit', 'badge-danger'], 'ubah_event' => ['Mengubah kegiatan', 'ph-calendar-dots', 'badge-brand'],
        'ubah_user' => ['Mengubah pengguna', 'ph-user-gear', 'badge-brand'],
    ];
@endphp
<x-page-header title="Audit" subtitle="Catatan peristiwa penting: masuk, penerbitan, unduh ulang, pembatalan, dan perubahan data." />

<form method="GET" class="flex gap-2 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 pb-1 mb-4" data-reveal>
    @foreach ($aksi as $key => [$lbl])
        <span class="relative">
            <input type="checkbox" class="chip-input" id="a-{{ $key }}" name="aksi[]" value="{{ $key }}" @checked(in_array($key, (array) request('aksi', []))) onchange="this.form.requestSubmit()">
            <label for="a-{{ $key }}" class="chip">{{ $lbl }}</label>
        </span>
    @endforeach
</form>

<section class="card p-2 sm:p-3" data-reveal style="--i:1">
    @forelse ($logs as $log)
        @php([$lbl, $icon, $badge] = $aksi[$log['aksi']])
        <article class="flex flex-wrap sm:flex-nowrap gap-x-4 gap-y-1 rounded-2xl p-3 sm:p-4 transition-colors hover:bg-alt" data-reveal style="--i: {{ $loop->index }}">
            <span class="badge {{ $badge }} !size-10 !p-0 justify-center !rounded-xl text-lg flex-none"><i class="ph {{ $icon }}"></i></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-fg"><span class="font-semibold">{{ $log['pelaku'] ?? 'Tidak dikenal' }}</span>
                    <span class="text-muted">{{ $lbl }}</span>
                    @if ($log['target']) <span class="num text-[13px] break-all">{{ $log['target'] }}</span> @endif
                </p>
                <p class="mt-0.5 text-sm">{{ $log['detail'] }}</p>
            </div>
            <div class="w-full sm:w-auto pl-14 sm:pl-0 sm:text-right text-xs text-muted flex-none">
                <p>{{ $log['waktu'] }}</p>
                <p class="num mt-0.5">{{ $log['ip'] }}</p>
            </div>
        </article>
    @empty
        <x-empty-state icon="ph-list-magnifying-glass" title="Belum ada catatan audit." />
    @endforelse
</section>
@endsection
