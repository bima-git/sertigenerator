@extends('layouts.app')
@section('title', 'Detail sertifikat')

@section('content')
@php
    $labels = [
        'judul_besar' => 'Judul besar', 'judul_kegiatan' => 'Judul pada sertifikat', 'bootcamp' => 'Bootcamp', 'program' => 'Program',
        'perusahaan' => 'Perusahaan penempatan', 'tanggal_mulai' => 'Tanggal mulai', 'tanggal_selesai' => 'Tanggal selesai',
        'kode' => 'Kode kegiatan', 'penerbit' => 'Penerbit', 'kota' => 'Kota terbit',
    ];
    $wide = ['judul_kegiatan', 'program', 'bootcamp'];
@endphp

<a href="{{ route('history.index') }}" class="btn btn-ghost btn-sm -ml-3 mb-4" data-reveal><i class="ph ph-arrow-left"></i>Riwayat</a>

<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
    <section class="card overflow-hidden" data-reveal style="--i:1">
        <div class="banner !rounded-none !min-h-0 p-6 sm:p-8">
            <span class="banner-shard s1"></span><span class="banner-shard s2"></span>
            <div class="relative z-[2]">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge {{ $c->jenis === 'seminar' ? 'badge-brand' : 'badge-info' }}">{{ $c->jenis === 'seminar' ? 'Seminar/Webinar' : 'Bootcamp' }}</span>
                    <x-status-badge :status="$c->status" :id="$c->id" />
                </div>
                <h1 class="mt-4 text-[28px] font-semibold !text-white">{{ $c->nama_peserta }}</h1>
                <p class="mt-1 text-white/70">{{ $c->event?->kode }} - {{ $c->event?->judul }}</p>
                <div class="mt-6 flex items-center gap-2">
                    @if ($c->nomor_lengkap)
                        <span class="num rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-[15px] backdrop-blur">{{ $c->nomor_lengkap }}</span>
                        <button type="button" class="btn btn-icon !text-white/70 hover:!bg-white/10 hover:!text-white" data-copy="{{ $c->nomor_lengkap }}" aria-label="Salin nomor"><i class="ph ph-copy"></i></button>
                    @else
                        <span class="text-white/70">Tanpa nomor</span>
                    @endif
                </div>
            </div>
        </div>

        @if ($c->status === 'dibatalkan')
            <div class="alert alert-danger m-5 mb-0"><i class="ph-fill ph-warning-octagon"></i><span>Sertifikat ini tidak berlaku. Unduh ulang menghasilkan PDF bertanda air DIBATALKAN.</span></div>
        @endif

        <div class="p-6 sm:p-8">
            <h2 class="text-lg font-semibold">Data tercetak</h2>
            <p class="hint mt-1">Salinan saat terbit, tidak berubah walau data kegiatan diubah. Layout versi {{ $c->versi_layout }}.</p>
            <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                <div data-reveal style="--i:2">
                    <dt class="text-sm text-muted">Tanggal terbit</dt>
                    <dd class="mt-1 text-fg">{{ $c->tanggal_terbit->translatedFormat('j F Y') }}</dd>
                </div>
                @foreach ($c->data_cetak ?? [] as $key => $val)
                    @continue($val === '' || $val === null)
                    <div class="{{ in_array($key, $wide) ? 'sm:col-span-2' : '' }}" data-reveal style="--i: {{ 3 + $loop->index }}">
                        <dt class="text-sm text-muted">{{ $labels[$key] ?? $key }}</dt>
                        <dd class="mt-1 text-fg">{{ is_array($val) ? implode(', ', $val) : $val }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <aside class="grid gap-5 content-start">
        <div class="card p-5 grid gap-2" data-reveal style="--i:2">
            <a href="{{ route('history.pdf', $c->id) }}" class="btn btn-primary w-full"><i class="ph ph-download-simple"></i>Unduh Ulang</a>
            @if ($me->peran === 'admin' && $c->status === 'terbit')
                <button type="button" class="btn btn-danger w-full" data-cancel-for="{{ $c->id }}" data-open="cancel-modal"
                        data-action="{{ route('history.cancel', $c->id) }}" data-nomor="{{ $c->nomor_lengkap ?? $c->nama_peserta }}"><i class="ph ph-prohibit"></i>Batalkan</button>
            @endif
        </div>

        <div class="card p-5" data-reveal style="--i:3">
            <h2 class="font-semibold">Riwayat status</h2>
            <ol class="mt-4 grid gap-5 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-px before:bg-line">
                <li class="relative flex gap-3">
                    <span class="relative grid place-items-center size-6 flex-none rounded-full bg-success-soft text-success text-xs"><i class="ph-fill ph-check"></i></span>
                    <div class="text-sm">
                        <p class="font-medium text-fg">Diterbitkan</p>
                        <p class="text-muted">{{ $c->created_at->translatedFormat('j F Y, H.i') }} oleh {{ $c->creator?->nama ?? 'pengguna terhapus' }}</p>
                    </div>
                </li>
                @if ($c->status === 'dibatalkan')
                    <li class="relative flex gap-3">
                        <span class="relative grid place-items-center size-6 flex-none rounded-full bg-danger-soft text-danger text-xs"><i class="ph-fill ph-x"></i></span>
                        <div class="text-sm">
                            <p class="font-medium text-fg">Dibatalkan</p>
                            <p class="text-muted">{{ $c->dibatalkan_pada?->translatedFormat('j F Y, H.i') }} oleh {{ $c->canceller?->nama }}</p>
                            <p class="mt-1">{{ $c->alasan_batal }}</p>
                        </div>
                    </li>
                @endif
            </ol>
        </div>
    </aside>
</div>

<x-cancel-modal />
@endsection
