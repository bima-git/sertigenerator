@extends('layouts.app')

@section('title', 'Pratinjau Sertifikat')

@section('content')
<x-page-header
    title="Pratinjau Sertifikat"
    subtitle="Periksa hasil render PDF contoh tanpa mengunduh berkas. Koordinat, garis bantu, dan tanda air mengikuti layout aktif."
>
    <x-slot:actions>
        <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" class="btn btn-secondary">
            <i class="ph ph-arrow-square-out"></i>Buka PDF
        </a>
    </x-slot:actions>
</x-page-header>

{{-- Kontrak: GET /pratinjau dengan parameter jenis, sampel, mode, guides, dan latar. --}}
<form method="GET" action="{{ route('pratinjau') }}" class="card p-5 sm:p-7 grid gap-5" data-reveal style="--i:1">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="field">
            <label class="label" for="pv-jenis">Jenis layout</label>
            <select id="pv-jenis" name="jenis" class="input" onchange="this.form.submit()">
                @foreach ($jenisTersedia as $item)
                    <option value="{{ $item }}" @selected($item === $jenis)>{{ ucfirst($item) }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="pv-sampel">Peserta contoh</label>
            <select id="pv-sampel" name="sampel" class="input" onchange="this.form.submit()">
                @foreach ($semuaSampel as $item)
                    <option value="{{ $item['berkas'] }}" @selected($item['berkas'] === $sampel['berkas'])>
                        {{ $item['data']['nama_peserta'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="pv-mode">Mode</label>
            <select id="pv-mode" name="mode" class="input" onchange="this.form.submit()">
                @foreach ($modeTersedia as $item)
                    <option value="{{ $item->value }}" @selected($item === $mode)>{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <label class="switch">
            <input type="checkbox" name="guides" value="1" @checked($guides) onchange="this.form.submit()">
            <span class="switch-track"></span>
            <span>
                <span class="block text-sm font-medium text-fg">Garis bantu</span>
                <span class="block text-xs text-muted">Grid 10 mm dan label koordinat elemen</span>
            </span>
        </label>

        <button type="submit" class="btn btn-primary">
            <i class="ph ph-arrows-clockwise"></i>Muat ulang pratinjau
        </button>
    </div>
</form>

<div class="card mt-6 overflow-hidden" data-reveal style="--i:2">
    <div class="flex flex-wrap items-center gap-2 border-b border-line px-5 py-3">
        <span class="badge badge-neutral"><i class="ph ph-file-pdf"></i>{{ $sampel['berkas'] }}</span>
        <span class="badge badge-brand">{{ ucfirst($jenis) }}</span>
        <span class="badge {{ $mode === \App\Enums\CertificateMode::Terbit ? 'badge-success' : 'badge-warning' }}">{{ $mode->label() }}</span>
        @if ($guides)
            <span class="badge badge-info">Garis bantu</span>
        @endif
        <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm ml-auto">
            <i class="ph ph-arrow-square-out"></i>Tab baru
        </a>
    </div>

    @if ($guides)
        <p class="px-5 py-3 text-sm text-muted">Garis bantu 10 mm dan koordinat elemen ikut tercetak.</p>
    @elseif ($mode !== \App\Enums\CertificateMode::Terbit)
        <p class="px-5 py-3 text-sm text-muted">Mode {{ $mode->label() }} menambahkan tanda air pada PDF.</p>
    @else
        <p class="px-5 py-3 text-sm text-muted">PDF ditampilkan langsung dari renderer, bukan berkas tersimpan.</p>
    @endif

    <iframe
        src="{{ $pdfUrl }}#view=Fit&navpanes=0"
        title="Pratinjau {{ $sampel['berkas'] }}"
        class="block w-full bg-white"
        style="aspect-ratio: 842 / 595; max-height: 78vh; height: auto"
    ></iframe>
</div>
@endsection
