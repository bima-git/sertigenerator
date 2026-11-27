@extends('layouts.app')
@section('title', 'Audit')

@section('content')
@php
    $aksi = [
        'masuk' => ['Masuk', 'ph-sign-in', 'badge-neutral'], 'keluar' => ['Keluar', 'ph-sign-out', 'badge-neutral'], 'reset_sandi' => ['Reset kata sandi', 'ph-key', 'badge-warning'], 'gagal_masuk' => ['Gagal masuk', 'ph-lock-key', 'badge-warning'],
        'terbit' => ['Menerbitkan', 'ph-seal-check', 'badge-success'], 'unduh_ulang' => ['Mengunduh ulang', 'ph-download-simple', 'badge-info'],
        'batal' => ['Membatalkan', 'ph-prohibit', 'badge-danger'], 'ubah_event' => ['Mengubah kegiatan', 'ph-calendar-dots', 'badge-brand'],
        'ubah_user' => ['Mengubah pengguna', 'ph-user-gear', 'badge-brand'],
    ];
@endphp
<x-page-header title="Audit" subtitle="Catatan peristiwa penting: masuk, penerbitan, unduh ulang, pembatalan, dan perubahan data." />

<form method="GET" data-live class="flex gap-2 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 pb-1 mb-4" data-reveal style="--i:1">
    @foreach ($aksi as $key => [$lbl])
        <span class="relative">
            <input type="checkbox" class="chip-input" id="a-{{ $key }}" name="aksi[]" value="{{ $key }}" @checked(in_array($key, (array) request('aksi', []))) onchange="this.form.requestSubmit()">
            <label for="a-{{ $key }}" class="chip">{{ $lbl }}</label>
        </span>
    @endforeach
</form>

<section class="card p-2 sm:p-3" data-reveal style="--i:2" data-live-region="list">
    @forelse ($logs as $log)
        @php([$lbl, $icon, $badge] = $aksi[$log->aksi] ?? [$log->aksi, 'ph-dot', 'badge-neutral'])
        @php($detail = $log->detail ?? [])
        <article class="flex flex-wrap sm:flex-nowrap gap-x-4 gap-y-1 rounded-2xl p-3 sm:p-4 transition-colors hover:bg-alt" data-reveal style="--i: {{ 3 + min($loop->index, 10) }}">
            <span class="badge {{ $badge }} !size-10 !p-0 justify-center !rounded-xl text-lg flex-none"><i class="ph {{ $icon }}"></i></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-fg"><span class="font-semibold">{{ $log->user?->nama ?? 'Tidak dikenal' }}</span>
                    <span class="text-muted">{{ mb_strtolower($lbl) }}</span>
                    @if (! empty($detail['nomor'])) <span class="num text-[13px] break-all">{{ $detail['nomor'] }}</span> @endif
                </p>
                <p class="mt-0.5 text-sm">{{ collect($detail)->except('nomor')->filter(fn ($v) => is_scalar($v))->implode(' · ') }}</p>
            </div>
            <div class="w-full sm:w-auto pl-14 sm:pl-0 sm:text-right text-xs text-muted flex-none">
                <p>{{ $log->created_at?->translatedFormat('j M Y, H.i') }}</p>
                <p class="num mt-0.5">{{ $log->ip }}</p>
            </div>
        </article>
    @empty
        <x-empty-state icon="ph-list-magnifying-glass" title="Belum ada catatan audit." />
    @endforelse
    @if ($logs->hasPages())
        <div class="border-t border-line p-4">{{ $logs->links() }}</div>
    @endif
</section>
@endsection
