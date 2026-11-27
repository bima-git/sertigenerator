@extends('layouts.app')
@section('title', 'Akun')

@section('content')
@php
    $fields = [
        'password_lama' => ['Kata sandi saat ini', 'current-password'],
        'password' => ['Kata sandi baru', 'new-password'],
        'password_confirmation' => ['Ulangi kata sandi baru', 'new-password'],
    ];
@endphp
<x-page-header title="Akun" />

<div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)] max-w-4xl">
    <section class="card p-6 text-center self-start" data-reveal style="--i:1">
        <span class="mx-auto grid place-items-center size-20 rounded-full bg-gradient-to-br from-accent to-brand text-2xl font-semibold text-white">{{ collect(explode(' ', $me->nama))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('') }}</span>
        <h2 class="mt-4 text-lg font-semibold">{{ $me->nama }}</h2>
        <p class="text-sm text-muted">{{ $me->username }}</p>
        @if ($me->email)
            <p class="mt-1 text-sm text-muted break-all"><i class="ph ph-envelope-simple"></i> {{ $me->email }}</p>
        @else
            <p class="mt-2 text-xs text-warning">Belum ada email. Minta Admin mengisinya agar bisa reset kata sandi sendiri.</p>
        @endif
        <span class="badge {{ $me->peran === 'admin' ? 'badge-brand' : 'badge-neutral' }} mt-3">{{ $me->peran === 'admin' ? 'Admin' : 'Operator' }}</span>
    </section>

    <form method="POST" action="{{ url('/akun/sandi') }}" class="card p-5 sm:p-7 grid gap-5" data-reveal style="--i:2">
        @csrf
        <h2 class="text-lg font-semibold">Ganti kata sandi</h2>
        @foreach ($fields as $name => [$lbl, $ac])
            <div class="field">
                <label for="{{ $name }}" class="label">{{ $lbl }}</label>
                <div class="relative">
                    <input id="{{ $name }}" name="{{ $name }}" type="password" class="input pr-12" autocomplete="{{ $ac }}" required @error($name) aria-invalid="true" @enderror>
                    <button type="button" class="btn btn-icon absolute right-1 top-1/2 -translate-y-1/2" data-toggle-password="{{ $name }}" aria-label="Tampilkan kata sandi"><i class="ph ph-eye"></i></button>
                </div>
                @if ($name === 'password') <p class="hint">Minimal 10 karakter.</p> @endif
                @error($name) <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>
        @endforeach
        <div class="flex justify-end">
            <button class="btn btn-primary" data-loading-text="Menyimpan"><i class="ph ph-floppy-disk"></i><span data-label>Simpan Kata Sandi</span></button>
        </div>
    </form>
</div>
@endsection
