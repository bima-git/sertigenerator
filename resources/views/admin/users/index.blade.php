@extends('layouts.app')
@section('title', 'Pengguna')

@section('content')
<x-page-header title="Pengguna" subtitle="Akun operator dan admin. Akun nonaktif tidak bisa masuk.">
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="ph ph-user-plus"></i>Tambah Pengguna</a>
    </x-slot:actions>
</x-page-header>

@if (session('temp_password'))
    <div class="alert alert-warning mb-4" role="alert">
        <i class="ph-fill ph-key"></i>
        <span>Kata sandi sementara: <span class="num font-semibold">{{ session('temp_password') }}</span>. Tampil sekali ini saja, sampaikan ke pengguna.</span>
    </div>
@endif

<section class="card overflow-hidden" data-reveal style="--i:1">
    <table class="table">
        <thead><tr><th>Nama</th><th>Peran</th><th>Dibuat</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
        <tbody>
            @foreach ($users as $u)
                <tr class="{{ $u['aktif'] ? '' : 'opacity-60' }}">
                    <td data-label="Nama">
                        <span class="flex items-center gap-3">
                            <span class="grid place-items-center size-9 flex-none rounded-full bg-brand-soft text-brand text-xs font-semibold">{{ collect(explode(' ', $u['nama']))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('') }}</span>
                            <span class="text-left"><span class="block font-medium text-fg">{{ $u['nama'] }}</span><span class="block text-xs text-muted">{{ $u['username'] }}</span><span class="block text-xs {{ $u['email'] ? 'text-muted' : 'text-warning' }}">{{ $u['email'] ?: 'Belum ada email' }}</span></span>
                        </span>
                    </td>
                    <td data-label="Peran"><span class="badge {{ $u['peran'] === 'admin' ? 'badge-brand' : 'badge-neutral' }}">{{ $u['peran'] === 'admin' ? 'Admin' : 'Operator' }}</span></td>
                    <td data-label="Dibuat" class="whitespace-nowrap">{{ $u->created_at?->translatedFormat('j M Y') }}</td>
                    <td data-label="Status"><span class="badge {{ $u['aktif'] ? 'badge-success' : 'badge-neutral' }}">{{ $u['aktif'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td data-label="">
                        <div class="flex justify-end gap-1">
                            <a href="{{ route('admin.users.edit', $u['id']) }}" class="btn btn-ghost btn-sm"><i class="ph ph-pencil-simple"></i>Ubah</a>
                            <form method="POST" action="{{ url('/admin/pengguna/'.$u['id'].'/reset-sandi') }}" data-confirm-title="Reset kata sandi {{ $u['nama'] }}?" data-confirm="Kata sandi lama tidak berlaku lagi. Kata sandi sementara tampil sekali setelah ini, sampaikan ke pengguna." data-confirm-tone="brand">
                                @csrf
                                <button class="btn btn-secondary btn-sm">Reset Sandi</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endsection
