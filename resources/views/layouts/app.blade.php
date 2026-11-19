@php
    // $me dari view composer di AppServiceProvider.
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
    $onIssue = request()->is('terbitkan*');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <title>@yield('title', 'Generator Sertifikat') | XCODE</title>
</head>
<body class="shell min-h-dvh antialiased">
<div class="lg:grid lg:grid-cols-[256px_1fr] min-h-dvh">

    {{-- Sidebar desktop --}}
    <aside class="vt-sidebar hidden lg:flex flex-col sticky top-0 h-dvh text-white overflow-hidden px-4 py-6">
        <div class="glow w-64 h-64 -left-24 -bottom-10 opacity-30"></div>
        <a href="{{ route('issue.form') }}" class="logo-host relative flex items-center gap-3 px-2 mb-8" aria-label="XCODE Generator Sertifikat">
            <x-logo variant="full" tone="light" class="h-7 w-auto" />
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

        <div class="promo logo-host mt-auto">
            <span class="grid place-items-center size-12 rounded-2xl bg-white shadow-lg">
                <x-logo class="w-9" animate="breathe" />
            </span>
            <p class="mt-4 font-semibold leading-snug">{{ $onIssue ? 'Cek sertifikat terbit' : 'Terbitkan lebih cepat' }}</p>
            <p class="mt-1 text-sm text-white/75">{{ $onIssue ? 'Unduh ulang atau salin nomor dari riwayat.' : 'Satu formulir untuk seminar dan bootcamp.' }}</p>
            <a href="{{ $onIssue ? route('history.index') : route('issue.form') }}" class="btn btn-sm mt-4 w-full bg-white text-fg hover:bg-zinc-100">
                {{ $onIssue ? 'Buka Riwayat' : 'Terbitkan Sekarang' }}
            </a>
        </div>
    </aside>

    {{-- Bar atas ponsel --}}
    <header class="vt-topbar lg:hidden sticky top-0 z-20 flex items-center justify-between h-16 px-4 text-white bg-side">
        <a href="{{ route('issue.form') }}" class="logo-host" aria-label="XCODE Generator Sertifikat">
            <x-logo variant="full" tone="light" class="h-6 w-auto" />
        </a>
        <a href="{{ route('account.edit') }}" class="grid place-items-center size-9 rounded-full bg-gradient-to-br from-accent to-brand text-xs font-semibold" aria-label="Akun">{{ $initials }}</a>
    </header>

    <main class="panel min-w-0 px-4 sm:px-6 lg:px-9 pt-6 lg:pt-6 pb-28 lg:pb-10">
        <div class="mx-auto max-w-[1240px]">
            {{-- Topbar panel (desktop) --}}
            <div class="vt-panelbar hidden lg:flex items-center justify-between gap-4 mb-8">
                <details class="relative">
                    <summary class="flex items-center gap-3 rounded-full py-1 pl-1 pr-3 transition-colors hover:bg-raised">
                        <span class="grid place-items-center size-11 rounded-full bg-gradient-to-br from-accent to-brand text-sm font-semibold text-white">{{ $initials }}</span>
                        <span class="leading-tight">
                            <span class="block font-semibold text-fg">{{ $me->nama }}</span>
                            <span class="block text-sm text-muted">{{ $isAdmin ? 'Admin' : 'Operator' }} · {{ $me->username }}</span>
                        </span>
                        <i class="ph ph-caret-down text-muted"></i>
                    </summary>
                    <div class="menu-pop grid">
                        <a href="{{ route('account.edit') }}" class="btn btn-ghost justify-start"><i class="ph ph-gear-six"></i>Akun</a>
                        <form method="POST" action="{{ url('/keluar') }}">
                            @csrf
                            <button class="btn btn-ghost justify-start w-full"><i class="ph ph-sign-out"></i>Keluar</button>
                        </form>
                    </div>
                </details>
                <div class="flex items-center gap-3">
                    <form action="{{ route('history.index') }}" method="GET" class="search-pill w-[320px]" role="search">
                        <i class="ph ph-magnifying-glass"></i>
                        <label for="top-search" class="sr-only">Cari sertifikat</label>
                        <input id="top-search" name="q" placeholder="Cari nama atau nomor sertifikat" value="{{ request()->is('riwayat') ? request('q') : '' }}">
                    </form>
                    @if ($isAdmin)
                        <a href="{{ route('admin.audit') }}" class="round-btn" aria-label="Aktivitas terbaru"><i class="ph ph-bell"></i></a>
                    @endif
                </div>
            </div>

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
    <a href="{{ route('issue.form') }}" class="tab-link" @if ($onIssue) aria-current="page" @endif><i class="ph ph-seal-check"></i>Terbitkan</a>
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
