@extends('layouts.app')
@section('title', $user ? 'Ubah pengguna' : 'Tambah pengguna')

@section('content')
@php
    $v = fn ($k, $d = null) => old($k, $user[$k] ?? $d);
    $self = $user && $user['id'] === $me->id;
@endphp
<a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm -ml-3 mb-4" data-reveal><i class="ph ph-arrow-left"></i>Pengguna</a>
<x-page-header :title="$user ? 'Ubah '.$user['nama'] : 'Tambah pengguna'" :subtitle="$user ? null : 'Kata sandi sementara dibuat sistem dan tampil sekali setelah disimpan.'" />

<form method="POST" action="{{ $user ? url('/admin/pengguna/'.$user['id'].'/ubah') : url('/admin/pengguna/baru') }}" class="card p-5 sm:p-7 max-w-2xl grid gap-5 md:grid-cols-2" data-reveal style="--i:1">
    @csrf
    <div class="field md:col-span-2">
        <label for="nama" class="label">Nama lengkap</label>
        <input id="nama" name="nama" class="input" maxlength="100" required value="{{ $v('nama') }}" @error('nama') aria-invalid="true" @enderror>
        @error('nama') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="username" class="label">Username</label>
        <input id="username" name="username" class="input" maxlength="50" required value="{{ $v('username') }}" autocomplete="off" @error('username') aria-invalid="true" @enderror>
        @error('username') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="peran" class="label">Peran</label>
        <select id="peran" name="peran" class="input" @disabled($self)>
            <option value="operator" @selected($v('peran') === 'operator')>Operator</option>
            <option value="admin" @selected($v('peran') === 'admin')>Admin</option>
        </select>
        @if ($self) <p class="hint">Peran akun sendiri tidak bisa diubah.</p> @endif
    </div>
    <label class="switch md:col-span-2 rounded-2xl bg-alt p-4">
        <input type="hidden" name="aktif" value="0">
        <input type="checkbox" name="aktif" value="1" @checked($v('aktif', true)) @disabled($self)>
        <span class="switch-track"></span>
        <span><span class="block text-sm font-medium text-fg">Akun aktif</span><span class="block text-xs text-muted">{{ $self ? 'Tidak tersedia untuk akun sendiri.' : 'Matikan untuk menonaktifkan tanpa menghapus riwayat.' }}</span></span>
    </label>
    @error('aktif') <p class="error md:col-span-2"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
    <div class="md:col-span-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end pt-2">
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Kembali</a>
        <button class="btn btn-primary" data-loading-text="Menyimpan"><i class="ph ph-floppy-disk"></i><span data-label>Simpan Pengguna</span></button>
    </div>
</form>
@endsection
