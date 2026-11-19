<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <link rel="preload" as="image" href="{{ asset('images/lobi-teknologi-server-indonesia.webp') }}"
          imagesrcset="{{ asset('images/lobi-teknologi-server-indonesia-900.webp') }} 900w, {{ asset('images/lobi-teknologi-server-indonesia.webp') }} 1672w" imagesizes="100vw">
    <title>Masuk | XCODE Sertifikat</title>
</head>
<body class="min-h-dvh antialiased bg-side">
{{-- Pola referensi SI Semar Sehat: foto lokasi penuh layar, judul di kiri, kartu kaca di kanan. --}}
<main class="login relative min-h-dvh overflow-hidden text-white">
    <img src="{{ asset('images/lobi-teknologi-server-indonesia.webp') }}"
         srcset="{{ asset('images/lobi-teknologi-server-indonesia-900.webp') }} 900w, {{ asset('images/lobi-teknologi-server-indonesia.webp') }} 1672w" sizes="100vw"
         alt="Lobi kantor PT Teknologi Server Indonesia" class="login-photo absolute inset-0 size-full object-cover object-[30%_center]" fetchpriority="high">
    <div class="login-scrim absolute inset-0" aria-hidden="true"></div>

    {{-- Ornamen: segitiga sertifikat dan motif {><} --}}
    <div class="login-shard s1" aria-hidden="true"></div>
    <div class="login-shard s2" aria-hidden="true"></div>
    <x-logo tone="light" class="login-motif w-24 bottom-[30%] left-[44%] hidden lg:block" aria-hidden="true" />

    <div class="relative z-10 mx-auto grid min-h-dvh max-w-[1280px] gap-8 px-4 py-6 sm:px-8 lg:grid-cols-[1fr_440px] lg:items-center lg:px-12">
        {{-- Judul --}}
        <section class="flex flex-col justify-between gap-10 lg:h-full lg:py-6">
            <div class="logo-host inline-flex w-fit items-center gap-3 rounded-full bg-white/10 py-2 pl-3 pr-4 backdrop-blur-md ring-1 ring-white/15" data-reveal>
                <x-logo variant="full" tone="light" class="h-6 w-auto" />
            </div>
            <div class="max-w-xl lg:pb-6">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tighter leading-[1.02] !text-white drop-shadow-[0_2px_24px_rgb(0_0_0/0.45)]" data-reveal style="--i:2">
                    Generator<br>Sertifikat <span class="text-accent">XCODE</span>
                </h1>
                <p class="mt-5 max-w-[46ch] text-base sm:text-lg text-white/85 leading-relaxed drop-shadow" data-reveal style="--i:3">
                    Terbitkan sertifikat seminar, webinar, dan bootcamp dengan nomor tersusun otomatis dan PDF siap unduh.
                </p>
            </div>
        </section>

        {{-- Kartu masuk --}}
        <section class="login-card w-full max-w-[440px] justify-self-center lg:justify-self-end p-6 sm:p-8 text-fg" data-reveal style="--i:2" aria-labelledby="login-title">
            <div class="logo-host flex items-center gap-4">
                <span class="grid place-items-center size-14 flex-none rounded-2xl bg-white shadow-md ring-1 ring-black/5">
                    <x-logo class="w-10" animate="assemble" />
                </span>
                <span class="leading-tight">
                    <span class="block text-lg font-bold text-fg">XCODE Training</span>
                    <span class="block text-sm font-medium text-body">PT Teknologi Server Indonesia</span>
                </span>
            </div>

            <h2 id="login-title" class="mt-7 text-[22px] font-semibold">Masuk ke Sistem</h2>
            <p class="mt-1 text-sm text-body">Masukkan username dan kata sandi untuk mengakses Sistem Sertifikat XCODE.</p>

            @if (session('status'))
                <div class="alert alert-info mt-5" role="status"><i class="ph-fill ph-info"></i><span>{{ session('status') }}</span></div>
            @endif
            @error('username')
                <div class="alert alert-danger mt-5" role="alert"><i class="ph-fill ph-warning-octagon"></i><span>{{ $message }}</span></div>
            @enderror

            <form method="POST" action="{{ url('/masuk') }}" class="mt-6 grid gap-4">
                @csrf
                <div class="field">
                    <label for="username" class="label">Username</label>
                    <div class="relative">
                        <i class="ph ph-user absolute left-4 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                        <input id="username" name="username" class="input login-input pl-11" value="{{ old('username') }}" autocomplete="username" autofocus required maxlength="50" @error('username') aria-invalid="true" @enderror>
                    </div>
                </div>
                <div class="field">
                    <label for="password" class="label">Kata sandi</label>
                    <div class="relative">
                        <i class="ph ph-lock-simple absolute left-4 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                        <input id="password" name="password" type="password" class="input login-input pl-11 pr-12" autocomplete="current-password" required>
                        <button type="button" class="btn btn-icon absolute right-1.5 top-1/2 -translate-y-1/2" data-toggle-password="password" aria-label="Tampilkan kata sandi"><i class="ph ph-eye"></i></button>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg w-full mt-2 group" data-loading-text="Memeriksa" @if (session('locked_seconds')) disabled @endif>
                    <span data-label>Masuk</span><i class="ph ph-arrow-right transition-transform group-hover:translate-x-1"></i>
                </button>
                @if (session('locked_seconds'))
                    <p class="hint text-center">Terlalu banyak percobaan. Coba lagi dalam {{ ceil(session('locked_seconds') / 60) }} menit.</p>
                @endif
            </form>

            <p class="mt-6 text-center text-xs text-muted">&copy; {{ date('Y') }} PT Teknologi Server Indonesia. Hak cipta dilindungi.</p>
        </section>
    </div>
</main>
</body>
</html>
