@extends('layouts.app')
@section('title', 'Detail sertifikat')

@section('content')
@php
    $me = auth()->user() ?? \App\Support\UiDummy::user();
    $labels = [
        'judul_cetak' => 'Judul pada sertifikat', 'bootcamp' => 'Bootcamp', 'program' => 'Program',
        'nama_peserta' => 'Nama peserta', 'perusahaan' => 'Perusahaan penempatan',
        'tanggal_mulai' => 'Tanggal mulai', 'tanggal_selesai' => 'Tanggal selesai', 'tanggal_kota' => 'Kota dan tanggal',
    ];
@endphp

<a href="{{ route('history.index') }}" class="btn btn-ghost btn-sm -ml-3 mb-4" data-reveal><i class="ph ph-arrow-left"></i>Riwayat</a>

<div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
    <section class="card overflow-hidden" data-reveal style="--i:1">
        <div class="relative overflow-hidden bg-side p-6 sm:p-8 text-white">
            <div class="glow w-72 h-72 -right-24 -top-32 opacity-40"></div>
            <div class="relative flex flex-wrap items-center gap-2">
                <span class="badge {{ $c['jenis'] === 'seminar' ? 'badge-brand' : 'badge-info' }}">{{ $c['jenis'] === 'seminar' ? 'Seminar/Webinar' : 'Bootcamp/Magang' }}</span>
                <x-status-badge :status="$c['status']" :id="$c['id']" />
            </div>
            <h1 class="relative mt-4 text-[28px] font-semibold !text-white">{{ $c['nama_peserta'] }}</h1>
            <p class="relative mt-1 text-zinc-400">{{ $c['event']['kode'] }} - {{ $c['event']['judul'] }}</p>
            <div class="relative mt-6 flex items-center gap-2">
                @if ($c['nomor_lengkap'])
                    <span class="num rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-[15px]">{{ $c['nomor_lengkap'] }}</span>
                    <button type="button" class="btn btn-icon !text-zinc-400 hover:!bg-white/10 hover:!text-white" data-copy="{{ $c['nomor_lengkap'] }}" aria-label="Salin nomor"><i class="ph ph-copy"></i></button>
                @else
                    <span class="text-zinc-400">Tanpa nomor</span>
                @endif
            </div>
        </div>

        @if ($c['status'] === 'dibatalkan')
            <div class="alert alert-danger m-5 mb-0"><i class="ph-fill ph-warning-octagon"></i><span>Sertifikat ini tidak berlaku. Unduh ulang menghasilkan PDF bertanda air DIBATALKAN.</span></div>
        @endif

        <div class="p-6 sm:p-8">
            <h2 class="text-lg font-semibold">Data tercetak</h2>
            <p class="hint mt-1">Salinan saat terbit. Tidak berubah walau data kegiatan diubah. Layout versi {{ $c['versi_layout'] }}.</p>
            <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                @foreach ($c['data_cetak'] as $key => $val)
                    <div class="{{ in_array($key, ['judul_cetak', 'program']) ? 'sm:col-span-2' : '' }}" data-reveal style="--i: {{ $loop->index }}">
                        <dt class="text-sm text-muted">{{ $labels[$key] ?? $key }}</dt>
                        <dd class="mt-1 text-fg">{{ $val }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <aside class="grid gap-4 content-start">
        <div class="card p-5 grid gap-2" data-reveal style="--i:2">
            <a href="{{ url('/riwayat/'.$c['id'].'/pdf') }}" class="btn btn-primary w-full"><i class="ph ph-download-simple"></i>Unduh Ulang</a>
            @if ($me->peran === 'admin' && $c['status'] === 'terbit')
                <button type="button" class="btn btn-danger w-full" data-cancel-for="{{ $c['id'] }}" data-open="cancel-modal"
                        data-action="{{ url('/riwayat/'.$c['id'].'/batalkan') }}" data-nomor="{{ $c['nomor_lengkap'] ?? $c['nama_peserta'] }}"><i class="ph ph-prohibit"></i>Batalkan</button>
            @endif
        </div>

        <div class="card p-5" data-reveal style="--i:3">
            <h2 class="font-semibold">Riwayat status</h2>
            <ol class="mt-4 grid gap-5 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-px before:bg-line">
                <li class="relative flex gap-3">
                    <span class="relative grid place-items-center size-6 flex-none rounded-full bg-success-soft text-success text-xs"><i class="ph-fill ph-check"></i></span>
                    <div class="text-sm">
                        <p class="font-medium text-fg">Diterbitkan</p>
                        <p class="text-muted">{{ $c['created_at']->translatedFormat('j F Y, H.i') }} oleh {{ $c['pembuat'] }}</p>
                    </div>
                </li>
                @if ($c['status'] === 'dibatalkan')
                    <li class="relative flex gap-3">
                        <span class="relative grid place-items-center size-6 flex-none rounded-full bg-danger-soft text-danger text-xs"><i class="ph-fill ph-x"></i></span>
                        <div class="text-sm">
                            <p class="font-medium text-fg">Dibatalkan</p>
                            <p class="text-muted">{{ $c['dibatalkan_pada']->translatedFormat('j F Y, H.i') }}</p>
                            <p class="mt-1">{{ $c['alasan_batal'] }}</p>
                        </div>
                    </li>
                @endif
            </ol>
        </div>
    </aside>
</div>

<x-cancel-modal />
@endsection
