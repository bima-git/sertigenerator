@props(['pageTitle', 'title', 'subtitle'])
{{-- Kerangka halaman tanpa sesi (Masuk, Lupa kata sandi, Reset kata sandi).
     Pola referensi SI Semar Sehat: foto lokasi penuh layar, judul di kiri, kartu kaca di kanan. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <link rel="preload" as="image" href="{{ asset('images/lobi-teknologi-server-indonesia.webp') }}"
          imagesrcset="{{ asset('images/lobi-teknologi-server-indonesia-900.webp') }} 900w, {{ asset('images/lobi-teknologi-server-indonesia.webp') }} 1672w" imagesizes="100vw">
    <title>{{ $pageTitle }} | XCODE Sertifikat</title>
</head>
<body class="min-h-dvh antialiased bg-side">
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

        {{-- Kartu --}}
        <section class="login-card w-full max-w-[440px] justify-self-center lg:justify-self-end p-6 sm:p-8 text-fg" data-reveal style="--i:2" aria-labelledby="auth-title">
            <div class="logo-host flex items-center gap-4">
                <span class="grid place-items-center size-14 flex-none rounded-2xl bg-white shadow-md ring-1 ring-black/5">
                    <x-logo class="w-10" animate="assemble" />
                </span>
                <span class="leading-tight">
                    <span class="block text-lg font-bold text-fg">XCODE Training</span>
                    <span class="block text-sm font-medium text-body">PT Teknologi Server Indonesia</span>
                </span>
            </div>

            <h2 id="auth-title" class="mt-7 text-[22px] font-semibold">{{ $title }}</h2>
            <p class="mt-1 text-sm text-body">{{ $subtitle }}</p>

            {{ $slot }}

            <p class="mt-6 text-center text-xs text-muted">&copy; {{ date('Y') }} PT Teknologi Server Indonesia. Hak cipta dilindungi.</p>
        </section>
    </div>
</main>
</body>
</html>
