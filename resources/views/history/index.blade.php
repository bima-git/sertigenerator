@extends('layouts.app')
@section('title', 'Riwayat')

@section('content')
@php
    $me = auth()->user() ?? \App\Support\UiDummy::user();
    $filtered = request()->hasAny(['q', 'jenis', 'status', 'dari', 'sampai']);
    $peak = max($monthly);
@endphp

<x-page-header title="Riwayat sertifikat" subtitle="Semua sertifikat yang pernah diterbitkan. Unduh ulang menghasilkan PDF yang sama dengan unduhan pertama.">
    <x-slot:actions>
        <a href="{{ route('issue.form') }}" class="btn btn-primary"><i class="ph ph-plus"></i>Terbitkan</a>
    </x-slot:actions>
</x-page-header>

{{-- Ringkasan --}}
<div class="grid gap-4 grid-cols-2 xl:grid-cols-4">
    <x-kpi label="Total terbit" :value="$stats['total']" icon="ph-seal-check" tone="brand" note="Termasuk yang dibatalkan" :i="0" />
    <x-kpi label="Seminar/Webinar" :value="$stats['seminar']" icon="ph-presentation-chart" tone="accent" :i="1" />
    <x-kpi label="Bootcamp/Magang" :value="$stats['bootcamp']" icon="ph-graduation-cap" tone="info" :i="2" />
    <x-kpi label="Dibatalkan" :value="$stats['batal']" icon="ph-prohibit" tone="success" note="Nomor tidak dipakai ulang" :i="3" />
</div>

<section class="card mt-4 p-5 sm:p-6" data-chart aria-labelledby="chart-title">
    <div class="flex items-center justify-between gap-4">
        <h2 id="chart-title" class="text-lg font-semibold">Penerbitan per bulan</h2>
        <span class="badge badge-neutral">6 bulan terakhir</span>
    </div>
    <div class="mt-6 flex h-48 items-end gap-3 sm:gap-6" role="list">
        @foreach ($monthly as $bulan => $jumlah)
            <div class="bar-col flex h-full flex-1 flex-col items-center gap-2" role="listitem" tabindex="0" aria-label="{{ $bulan }}: {{ $jumlah }} sertifikat">
                <div class="relative flex w-full max-w-14 flex-1 items-end">
                    <div class="bar {{ $jumlah === $peak ? 'is-peak' : '' }}" style="height: {{ round($jumlah / $peak * 100) }}%; --i: {{ $loop->index }}"></div>
                    <span class="bar-tip num absolute left-1/2 -translate-x-1/2 -top-1 -translate-y-full rounded-lg bg-side px-2 py-1 text-xs font-semibold text-white">{{ $jumlah }}</span>
                </div>
                <span class="text-xs text-muted">{{ $bulan }}</span>
            </div>
        @endforeach
    </div>
</section>

{{-- Saringan --}}
<form method="GET" class="card mt-4 p-4 sm:p-5 grid gap-4" data-reveal role="search">
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
<section class="card mt-4 overflow-hidden" data-reveal aria-label="Daftar sertifikat">
    @if (count($certificates) === 0)
        @if ($filtered)
            <x-empty-state icon="ph-magnifying-glass" title="Tidak ada sertifikat yang cocok dengan saringan ini." />
        @else
            <x-empty-state icon="ph-certificate" title="Belum ada sertifikat. Terbitkan yang pertama." action="Terbitkan" :href="route('issue.form')" />
        @endif
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu terbit</th><th>Peserta</th><th>Nomor</th><th>Pembuat</th><th>Status</th><th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($certificates as $c)
                        <tr>
                            <td data-label="Waktu terbit" class="whitespace-nowrap">
                                <span class="text-fg">{{ $c['created_at']->translatedFormat('j M Y') }}</span>
                                <span class="block text-xs text-muted">{{ $c['created_at']->format('H.i') }}</span>
                            </td>
                            <td data-label="Peserta">
                                <a href="{{ route('history.show', $c['id']) }}" class="font-medium text-fg hover:text-brand">{{ $c['nama_peserta'] }}</a>
                                <span class="mt-0.5 flex items-center gap-1.5 text-xs text-muted max-md:justify-end">
                                    <span class="badge {{ $c['jenis'] === 'seminar' ? 'badge-brand' : 'badge-info' }} !h-5 !px-2">{{ $c['jenis'] === 'seminar' ? 'Seminar' : 'Bootcamp' }}</span>
                                    <span class="truncate max-w-[180px]">{{ $c['event']['judul'] }}</span>
                                </span>
                            </td>
                            <td data-label="Nomor">
                                @if ($c['nomor_lengkap'])
                                    <span class="inline-flex items-center gap-1">
                                        <span class="num text-[13px] text-fg">{{ $c['nomor_lengkap'] }}</span>
                                        <button type="button" class="btn btn-icon !size-8" data-copy="{{ $c['nomor_lengkap'] }}" aria-label="Salin nomor {{ $c['nomor_lengkap'] }}"><i class="ph ph-copy"></i></button>
                                    </span>
                                @else
                                    <span class="text-muted">Tanpa nomor</span>
                                @endif
                            </td>
                            <td data-label="Pembuat" class="whitespace-nowrap">{{ $c['pembuat'] }}</td>
                            <td data-label="Status"><x-status-badge :status="$c['status']" :id="$c['id']" /></td>
                            <td data-label="">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('history.show', $c['id']) }}" class="btn btn-ghost btn-sm">Lihat</a>
                                    <a href="{{ url('/riwayat/'.$c['id'].'/pdf') }}" class="btn btn-secondary btn-sm !w-9 !px-0" aria-label="Unduh ulang PDF {{ $c['nama_peserta'] }}"
                                       title="{{ $c['status'] === 'dibatalkan' ? 'Unduh ulang (bertanda air DIBATALKAN)' : 'Unduh ulang' }}"><i class="ph ph-download-simple"></i></a>
                                    @if ($me->peran === 'admin' && $c['status'] === 'terbit')
                                        <button type="button" class="btn btn-danger btn-sm" data-cancel-for="{{ $c['id'] }}"
                                                data-open="cancel-modal" data-action="{{ url('/riwayat/'.$c['id'].'/batalkan') }}" data-nomor="{{ $c['nomor_lengkap'] ?? $c['nama_peserta'] }}">Batalkan</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($certificates instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="border-t border-line p-4">{{ $certificates->withQueryString()->links() }}</div>
        @endif
    @endif
</section>

<x-cancel-modal />
@endsection
