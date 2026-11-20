@extends('layouts.app')
@section('title', 'Kegiatan')

@section('content')
<x-page-header title="Kegiatan" subtitle="Kode, penerbit, dan pengaturan nomor setiap kegiatan. Hanya kegiatan aktif yang muncul di form Terbitkan.">
    <x-slot:actions>
        <a href="{{ route('admin.events.create') }}" class="btn btn-primary"><i class="ph ph-plus"></i>Tambah Kegiatan</a>
    </x-slot:actions>
</x-page-header>

<form method="GET" class="flex flex-col gap-3 sm:flex-row mb-4" data-reveal role="search">
    <div class="relative flex-1">
        <label for="q" class="sr-only">Cari kode atau judul</label>
        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
        <input id="q" name="q" value="{{ request('q') }}" class="input pl-10" placeholder="Cari kode atau judul">
    </div>
    <label for="jenis" class="sr-only">Jenis</label>
    <select id="jenis" name="jenis" class="input sm:w-52" onchange="this.form.requestSubmit()">
        <option value="">Semua jenis</option>
        <option value="seminar" @selected(request('jenis') === 'seminar')>Seminar/Webinar</option>
        <option value="bootcamp" @selected(request('jenis') === 'bootcamp')>Bootcamp/Magang</option>
    </select>
</form>

<section class="card overflow-hidden" data-reveal style="--i:1">
    @if (count($events) === 0)
        <x-empty-state icon="ph-calendar-plus" title="Belum ada kegiatan. Tambahkan kegiatan pertama agar operator bisa menerbitkan." action="Tambah Kegiatan" :href="route('admin.events.create')" />
    @else
        <table class="table">
            <thead><tr><th>Kode</th><th>Judul</th><th>Jenis</th><th>Penerbit</th><th>Nomor dan QR</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
                @foreach ($events as $e)
                    <tr class="{{ $e['aktif'] ? '' : 'opacity-60' }}">
                        <td data-label="Kode"><span class="num font-semibold text-brand">{{ $e['kode'] }}</span></td>
                        <td data-label="Judul" class="text-fg font-medium md:max-w-[340px]">{{ $e['judul'] }}</td>
                        <td data-label="Jenis"><span class="badge {{ $e['jenis'] === 'seminar' ? 'badge-brand' : 'badge-info' }}">{{ $e['jenis'] === 'seminar' ? 'Seminar' : 'Bootcamp' }}</span></td>
                        <td data-label="Penerbit" class="num text-[13px]">{{ $e['penerbit'] }}</td>
                        <td data-label="Nomor dan QR" class="whitespace-nowrap text-[13px]">
                            <span class="{{ $e['bernomor'] ? 'text-fg' : 'text-muted' }}"><i class="ph {{ $e['bernomor'] ? 'ph-hash' : 'ph-minus' }}"></i> {{ $e['bernomor'] ? 'Bernomor' : 'Tanpa nomor' }}</span>
                            <span class="ml-3 {{ $e['pakai_qr'] ? 'text-fg' : 'text-muted' }}"><i class="ph {{ $e['pakai_qr'] ? 'ph-qr-code' : 'ph-minus' }}"></i> {{ $e['pakai_qr'] ? 'QR' : 'Tanpa QR' }}</span>
                        </td>
                        <td data-label="Status"><span class="badge {{ $e['aktif'] ? 'badge-success' : 'badge-neutral' }}">{{ $e['aktif'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td data-label="">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.events.edit', $e['id']) }}" class="btn btn-ghost btn-sm"><i class="ph ph-pencil-simple"></i>Ubah</a>
                                <form method="POST" action="{{ url('/admin/kegiatan/'.$e['id'].'/aktif') }}" @if ($e['aktif']) data-confirm="Nonaktifkan kegiatan {{ $e['kode'] }}? Kegiatan tidak muncul lagi di form Terbitkan." @endif>
                                    @csrf
                                    <button class="btn btn-sm {{ $e['aktif'] ? 'btn-danger' : 'btn-secondary' }}">{{ $e['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection
