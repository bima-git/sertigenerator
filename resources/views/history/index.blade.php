@extends('layouts.app')
@section('title', 'Riwayat')

@section('content')
@php
    $filtered = request()->hasAny(['q', 'jenis', 'status', 'dari', 'sampai']);
    $peak = max([1, ...array_values($monthly)]);
    $hour = (int) now()->format('G');
    $salam = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $aksi = [
        'masuk' => ['masuk ke sistem', 'ph-sign-in', 'badge-neutral'], 'gagal_masuk' => ['gagal masuk', 'ph-lock-key', 'badge-warning'],
        'terbit' => ['menerbitkan', 'ph-seal-check', 'badge-success'], 'unduh_ulang' => ['mengunduh ulang', 'ph-download-simple', 'badge-info'],
        'batal' => ['membatalkan', 'ph-prohibit', 'badge-danger'], 'ubah_event' => ['mengubah kegiatan', 'ph-calendar-dots', 'badge-brand'],
        'ubah_user' => ['mengubah pengguna', 'ph-user-gear', 'badge-brand'],
    ];
@endphp

{{-- Banner ringkasan --}}
<section class="banner px-6 py-7 sm:px-9 sm:py-9" data-reveal aria-label="Ringkasan">
    <span class="banner-shard s1"></span><span class="banner-shard s2"></span><span class="banner-shard s3"></span>
    <div class="relative z-[2] max-w-[620px]">
        <p class="text-sm text-white/70">{{ $salam }}, {{ explode(' ', $me->nama)[0] }}</p>
        <h1 class="mt-2 text-[28px] sm:text-[34px] font-semibold leading-tight !text-white">
            <span class="count num" style="--to: {{ $stats['bulan_ini'] }}" aria-hidden="true"></span><span class="sr-only">{{ $stats['bulan_ini'] }}</span>
            sertifikat terbit bulan ini
        </h1>
        <p class="mt-2 text-white/75 max-w-[46ch]">Semua sertifikat tersimpan di sini. Unduh ulang menghasilkan PDF yang sama dengan unduhan pertama.</p>
        <a href="{{ route('issue.form') }}" class="btn mt-6 bg-white text-fg hover:bg-zinc-100 shadow-lg"><i class="ph ph-plus"></i>Terbitkan</a>
    </div>
    <div class="ribbon hidden md:grid logo-host" aria-hidden="true">
        <span class="ribbon-badge"><x-logo class="w-12" animate="breathe" /></span>
        <span class="text-[13px] font-black tracking-tight">XCODE</span>
    </div>
</section>

{{-- KPI --}}
<div class="mt-5 grid gap-4 grid-cols-2 xl:grid-cols-4">
    <x-kpi label="Total terbit" :value="$stats['total']" icon="ph-seal-check" tone="brand" note="Termasuk yang dibatalkan" :i="1" :spark="array_values($monthly)" />
    <x-kpi label="Seminar/Webinar" :value="$stats['seminar']" icon="ph-presentation-chart" tone="accent" :i="2" />
    <x-kpi label="Bootcamp" :value="$stats['bootcamp']" icon="ph-graduation-cap" tone="info" :i="3" />
    <x-kpi label="Dibatalkan" :value="$stats['batal']" icon="ph-prohibit" tone="success" note="Nomor tidak dipakai ulang" :i="4" />
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
    {{-- Grafik --}}
    <section class="card p-5 sm:p-6 flex flex-col" data-reveal style="--i:5" aria-labelledby="chart-title">
        <div class="flex items-center justify-between gap-4">
            <h2 id="chart-title" class="text-lg font-semibold">Penerbitan per bulan</h2>
            <span class="badge badge-neutral">6 bulan terakhir</span>
        </div>
        <div class="mt-8 flex min-h-52 flex-1 items-end gap-3 sm:gap-6" role="list">
            @foreach ($monthly as $bulan => $jumlah)
                <div class="bar-col flex h-full flex-1 flex-col items-center gap-2" role="listitem" tabindex="0" aria-label="{{ $bulan }}: {{ $jumlah }} sertifikat">
                    <div class="relative flex w-full max-w-11 flex-1 items-end">
                        <div class="bar {{ $jumlah === $peak && $jumlah > 0 ? 'is-peak' : '' }}" style="height: {{ max(4, round($jumlah / $peak * 100)) }}%; --i: {{ $loop->index }}"></div>
                        <span class="bar-tip num absolute left-1/2 -translate-x-1/2 -top-1 -translate-y-full rounded-lg bg-side px-2 py-1 text-xs font-semibold text-white">{{ $jumlah }}</span>
                    </div>
                    <span class="text-xs text-muted">{{ $bulan }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Aktivitas terbaru --}}
    <section class="card p-5" data-reveal style="--i:6" aria-labelledby="act-title">
        <div class="flex items-center justify-between">
            <h2 id="act-title" class="text-lg font-semibold">Aktivitas terbaru</h2>
            @if ($me->peran === 'admin')
                <a href="{{ route('admin.audit') }}" class="btn btn-ghost btn-sm -mr-2">Semua</a>
            @endif
        </div>
        <ul class="mt-4 grid grid-cols-[minmax(0,1fr)] gap-2">
            @forelse ($activity as $log)
                @php([$lbl, $icon, $badge] = $aksi[$log->aksi] ?? [$log->aksi, 'ph-dot', 'badge-neutral'])
                <li class="flex items-center gap-3 rounded-2xl bg-alt p-2.5 transition-transform hover:translate-x-0.5" data-reveal style="--i: {{ 7 + $loop->index }}">
                    <span class="badge {{ $badge }} !size-9 !p-0 justify-center !rounded-xl text-base flex-none"><i class="ph {{ $icon }}"></i></span>
                    <span class="min-w-0 text-sm">
                        <span class="block truncate text-fg"><span class="font-semibold">{{ $log->user?->nama ?? 'Sistem' }}</span> {{ $lbl }}</span>
                        <span class="block truncate text-xs text-muted">{{ $log->detail['nama'] ?? $log->detail['nomor'] ?? '' }} · {{ $log->created_at?->diffForHumans() }}</span>
                    </span>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-muted">Belum ada aktivitas.</li>
            @endforelse
        </ul>
    </section>
</div>

{{-- Saringan --}}
<form method="GET" class="card mt-5 p-4 sm:p-5 grid gap-4" data-reveal style="--i:7" role="search">
    <div class="grid gap-3 md:grid-cols-[1fr_auto_auto_auto]">
        <div class="relative">
            <label for="q" class="sr-only">Cari nama atau nomor</label>
            <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
            <input id="q" name="q" value="{{ request('q') }}" class="input pl-10" placeholder="Cari nama atau nomor sertifikat">
        </div>
        <label class="sr-only" for="dari">Dari tanggal</label>
        <input id="dari" name="dari" type="date" class="input" value="{{ request('dari') }}" title="Dari tanggal">
        <label class="sr-only" for="sampai">Sampai tanggal</label>
        <input id="sampai" name="sampai" type="date" class="input" value="{{ request('sampai') }}" title="Sampai tanggal">
        <button class="btn btn-primary"><i class="ph ph-funnel"></i>Cari</button>
    </div>
    <div class="flex gap-2 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 pb-1">
        @foreach (['jenis' => ['seminar' => 'Seminar', 'bootcamp' => 'Bootcamp'], 'status' => ['terbit' => 'Terbit', 'dibatalkan' => 'Dibatalkan']] as $param => $opts)
            @foreach ($opts as $val => $lbl)
                <span class="relative">
                    <input type="checkbox" class="chip-input" id="f-{{ $val }}" name="{{ $param }}[]" value="{{ $val }}" @checked(in_array($val, (array) request($param, []))) onchange="this.form.requestSubmit()">
                    <label for="f-{{ $val }}" class="chip">{{ $lbl }}</label>
                </span>
            @endforeach
            @if ($loop->first) <span class="w-px flex-none bg-line mx-1" aria-hidden="true"></span> @endif
        @endforeach
        @if ($filtered)
            <a href="{{ route('history.index') }}" class="btn btn-ghost btn-sm ml-auto"><i class="ph ph-x"></i>Hapus Saringan</a>
        @endif
    </div>
</form>

{{-- Daftar --}}
<section class="card mt-5 overflow-hidden" data-reveal style="--i:8" aria-label="Daftar sertifikat">
    @if ($certificates->isEmpty())
        @if ($filtered)
            <x-empty-state icon="ph-magnifying-glass" title="Tidak ada sertifikat yang cocok dengan saringan ini." />
        @else
            <x-empty-state icon="ph-certificate" title="Belum ada sertifikat. Terbitkan yang pertama." action="Terbitkan" :href="route('issue.form')" />
        @endif
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Waktu terbit</th><th>Peserta</th><th>Nomor</th><th>Pembuat</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($certificates as $c)
                        <tr>
                            <td data-label="Waktu terbit" class="whitespace-nowrap">
                                <span class="text-fg">{{ $c->created_at->translatedFormat('j M Y') }}</span>
                                <span class="block text-xs text-muted">{{ $c->created_at->format('H.i') }}</span>
                            </td>
                            <td data-label="Peserta">
                                <a href="{{ route('history.show', $c->id) }}" class="font-medium text-fg hover:text-brand">{{ $c->nama_peserta }}</a>
                                <span class="mt-0.5 flex items-center gap-1.5 text-xs text-muted max-md:justify-end">
                                    <span class="badge {{ $c->jenis === 'seminar' ? 'badge-brand' : 'badge-info' }} !h-5 !px-2">{{ $c->jenis === 'seminar' ? 'Seminar' : 'Bootcamp' }}</span>
                                    <span class="truncate max-w-[180px]">{{ $c->event?->judul }}</span>
                                </span>
                            </td>
                            <td data-label="Nomor">
                                @if ($c->nomor_lengkap)
                                    <span class="inline-flex items-center gap-1">
                                        <span class="num text-[13px] text-fg">{{ $c->nomor_lengkap }}</span>
                                        <button type="button" class="btn btn-icon !size-8" data-copy="{{ $c->nomor_lengkap }}" aria-label="Salin nomor {{ $c->nomor_lengkap }}"><i class="ph ph-copy"></i></button>
                                    </span>
                                @else
                                    <span class="text-muted">Tanpa nomor</span>
                                @endif
                            </td>
                            <td data-label="Pembuat" class="whitespace-nowrap">{{ $c->creator?->nama }}</td>
                            <td data-label="Status"><x-status-badge :status="$c->status" :id="$c->id" /></td>
                            <td data-label="">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('history.show', $c->id) }}" class="btn btn-ghost btn-sm">Lihat</a>
                                    <a href="{{ route('history.pdf', $c->id) }}" class="btn btn-secondary btn-sm !w-9 !px-0" aria-label="Unduh ulang PDF {{ $c->nama_peserta }}"
                                       title="{{ $c->status === 'dibatalkan' ? 'Unduh ulang (bertanda air DIBATALKAN)' : 'Unduh ulang' }}"><i class="ph ph-download-simple"></i></a>
                                    @if ($me->peran === 'admin' && $c->status === 'terbit')
                                        <button type="button" class="btn btn-danger btn-sm" data-cancel-for="{{ $c->id }}"
                                                data-open="cancel-modal" data-action="{{ route('history.cancel', $c->id) }}" data-nomor="{{ $c->nomor_lengkap ?? $c->nama_peserta }}">Batalkan</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($certificates->hasPages())
            <div class="border-t border-line p-4">{{ $certificates->links() }}</div>
        @endif
    @endif
</section>

<x-cancel-modal />
@endsection
