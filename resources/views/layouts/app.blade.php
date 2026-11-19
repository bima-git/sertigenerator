@php
    // ponytail: UiDummy sampai autentikasi Akmal digabung.
    $me = auth()->user() ?? \App\Support\UiDummy::user();
    $isAdmin = $me->peran === 'admin';
    $nav = [
        ['issue.form', 'Terbitkan', 'ph-seal-check', 'terbitkan*'],
        ['history.index', 'Riwayat', 'ph-clock-counter-clockwise', 'riwayat*'],
    ];
    $adminNav = [
        ['admin.events.index', 'Kegiatan', 'ph-calendar-dots', 'admin/kegiatan*'],
        ['admin.users.index', 'Pengguna', 'ph-users-three', 'admin/pengguna*'],
        ['admin.layout.test', 'Uji Layout', 'ph-ruler', 'admin/uji-layout*'],
        ['admin.audit', 'Audit', 'ph-list-magnifying-glass', 'admin/audit*'],
    ];
    $initials = collect(explode(' ', $me->nama))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <title>@yield('title', 'Generator Sertifikat') | XCODE</title>
</head>
<body class="min-h-dvh antialiased">
<div class="lg:grid lg:grid-cols-[264px_1fr] min-h-dvh">

    {{-- Sidebar desktop --}}
    <aside class="vt-sidebar hidden lg:flex flex-col sticky top-0 h-dvh bg-side text-white overflow-hidden p-4">
        <div class="glow w-64 h-64 -left-24 -bottom-20 opacity-40"></div>
        <a href="{{ route('issue.form') }}" class="flex items-center gap-3 px-3 h-14 mb-6">
            <span class="grid place-items-center size-9 rounded-xl bg-brand text-white font-bold tracking-tight">X</span>
            <span class="leading-tight">
                <span class="block font-semibold tracking-tight">XCODE</span>
                <span class="block text-xs text-zinc-400">Generator Sertifikat</span>
            </span>
        </a>

        <nav class="relative grid gap-1" data-side-nav aria-label="Navigasi utama">
            <span class="side-hover" aria-hidden="true"></span>
            @foreach ($nav as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" class="side-link" @if (request()->is($pattern)) aria-current="page" @endif>
                    <i class="ph {{ $icon }}"></i>{{ $label }}
                </a>
            @endforeach
            @if ($isAdmin)
                <p class="px-4 pt-5 pb-2 text-xs font-medium text-zinc-500">Admin</p>
                @foreach ($adminNav as [$route, $label, $icon, $pattern])
                    <a href="{{ route($route) }}" class="side-link" @if (request()->is($pattern)) aria-current="page" @endif>
                        <i class="ph {{ $icon }}"></i>{{ $label }}
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="relative mt-auto rounded-2xl border border-white/10 bg-white/[0.04] p-3">
            <div class="flex items-center gap-3">
                <span class="grid place-items-center size-10 rounded-full bg-gradient-to-br from-accent to-brand text-sm font-semibold">{{ $initials }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold">{{ $me->nama }}</span>
                    <span class="block text-xs text-zinc-400">{{ $isAdmin ? 'Admin' : 'Operator' }}</span>
                </span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="{{ route('account.edit') }}" class="btn btn-sm justify-center text-zinc-200 bg-white/5 hover:bg-white/10"><i class="ph ph-gear-six"></i>Akun</a>
                <form method="POST" action="{{ url('/keluar') }}">
                    @csrf
                    <button class="btn btn-sm w-full text-zinc-200 bg-white/5 hover:bg-white/10"><i class="ph ph-sign-out"></i>Keluar</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Bar atas ponsel --}}
    <header class="vt-topbar lg:hidden sticky top-0 z-20 flex items-center justify-between h-16 px-4 bg-side text-white">
        <a href="{{ route('issue.form') }}" class="flex items-center gap-2.5">
            <span class="grid place-items-center size-8 rounded-lg bg-brand font-bold">X</span>
            <span class="font-semibold tracking-tight">XCODE Sertifikat</span>
        </a>
        <span class="grid place-items-center size-9 rounded-full bg-gradient-to-br from-accent to-brand text-xs font-semibold">{{ $initials }}</span>
    </header>

    <main class="min-w-0 px-4 sm:px-6 lg:px-10 pt-6 lg:pt-10 pb-28 lg:pb-12">
        <div class="mx-auto max-w-[1200px]">
            @if (session('success'))
                <div class="alert alert-success mb-6" role="status" data-flash="success"><i class="ph-fill ph-check-circle"></i><span>{{ session('success') }}</span></div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mb-6" role="alert" data-flash="danger"><i class="ph-fill ph-warning-octagon"></i><span>{{ session('error') }}</span></div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

{{-- Navigasi bawah ponsel --}}
<nav class="vt-tabbar lg:hidden fixed inset-x-0 bottom-0 z-20 grid grid-cols-3 bg-raised/95 backdrop-blur border-t border-line pb-[env(safe-area-inset-bottom)]" aria-label="Navigasi utama">
    <a href="{{ route('issue.form') }}" class="tab-link" @if (request()->is('terbitkan*')) aria-current="page" @endif><i class="ph ph-seal-check"></i>Terbitkan</a>
    <a href="{{ route('history.index') }}" class="tab-link" @if (request()->is('riwayat*')) aria-current="page" @endif><i class="ph ph-clock-counter-clockwise"></i>Riwayat</a>
    <button type="button" class="tab-link" data-menu-toggle @if (request()->is('admin*', 'akun*')) aria-current="page" @endif><i class="ph ph-dots-three-circle"></i>Menu</button>
</nav>

<dialog id="mobile-menu" class="modal !mt-auto !mb-0 !w-full !max-w-none !rounded-b-none" aria-label="Menu">
    <div class="p-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
        <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-line"></div>
        <div class="grid gap-1">
            @if ($isAdmin)
                @foreach ($adminNav as [$route, $label, $icon])
                    <a href="{{ route($route) }}" class="btn btn-ghost justify-start"><i class="ph {{ $icon }} text-brand"></i>{{ $label }}</a>
                @endforeach
                <hr class="my-2 border-line">
            @endif
            <a href="{{ route('account.edit') }}" class="btn btn-ghost justify-start"><i class="ph ph-gear-six"></i>Akun</a>
            <form method="POST" action="{{ url('/keluar') }}">
                @csrf
                <button class="btn btn-ghost justify-start w-full"><i class="ph ph-sign-out"></i>Keluar</button>
            </form>
        </div>
    </div>
</dialog>

<div id="toasts" class="fixed top-4 right-4 left-4 sm:left-auto sm:w-[400px] z-50 grid gap-2 pointer-events-none" aria-live="polite"></div>
</body>
</html>
