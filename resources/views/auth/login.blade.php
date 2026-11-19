<!DOCTYPE html>
<html lang="id" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk | XCODE Sertifikat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh antialiased bg-side lg:bg-surface">
<main class="min-h-dvh grid lg:grid-cols-[1.1fr_1fr]">

    {{-- Panel merek --}}
    <section class="relative overflow-hidden bg-side text-white px-6 pt-10 pb-8 sm:px-10 lg:p-14 flex flex-col">
        <div class="glow w-[420px] h-[420px] -right-32 top-1/3 opacity-50"></div>
        <div class="glow w-72 h-72 -left-24 -bottom-24 opacity-30" style="animation-delay: -6s"></div>
        <div class="absolute inset-0 opacity-[0.07] [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:28px_28px]" aria-hidden="true"></div>

        <div class="relative flex items-center gap-3" data-reveal>
            <span class="grid place-items-center size-10 rounded-xl bg-brand font-bold">X</span>
            <span class="font-semibold tracking-tight text-lg">XCODE</span>
        </div>

        <div class="relative my-auto py-10 lg:py-0 max-w-xl">
            <h1 class="text-4xl lg:text-5xl font-semibold tracking-tighter leading-[1.05] !text-white" data-reveal style="--i:1">
                Sertifikat kegiatan, <span class="bg-gradient-to-r from-accent to-orange-300 bg-clip-text text-transparent">terbit tanpa ketik ulang.</span>
            </h1>
            <p class="mt-5 text-zinc-400 max-w-[46ch]" data-reveal style="--i:2">Pilih kegiatan, isi nama peserta, dan nomor sertifikat tersusun otomatis.</p>

            <div class="mt-10 hidden sm:flex items-end gap-1.5 font-mono" data-reveal style="--i:3" aria-label="Contoh nomor sertifikat 35/001/003/Xcodetraining/X/2026">
                @foreach ([['35', 'kode'], ['001', 'seri'], ['003', 'urut'], ['Xcodetraining', 'penerbit'], ['X', 'bulan'], ['2026', 'tahun']] as $k => [$v, $l])
                    @if ($k) <span class="pb-6 text-zinc-600">/</span> @endif
                    <span class="grid justify-items-center gap-1.5">
                        <span class="rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-sm {{ $l === 'urut' ? 'text-accent border-accent/40' : 'text-zinc-200' }}">{{ $v }}</span>
                        <span class="text-[11px] font-sans text-zinc-500">{{ $l }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Form --}}
    <section class="flex items-start lg:items-center justify-center px-4 pb-10 lg:p-10 -mt-4 lg:mt-0 relative">
        <div class="card w-full max-w-[420px] p-6 sm:p-8" data-reveal style="--i:2">
            <h2 class="text-[22px] font-semibold">Masuk</h2>
            <p class="mt-1 text-sm text-muted">Gunakan akun yang diberikan admin.</p>

            @if (session('status'))
                <div class="alert alert-info mt-5" role="status"><i class="ph-fill ph-info"></i><span>{{ session('status') }}</span></div>
            @endif
            @error('username')
                <div class="alert alert-danger mt-5" role="alert"><i class="ph-fill ph-warning-octagon"></i><span>{{ $message }}</span></div>
            @enderror

            <form method="POST" action="{{ url('/masuk') }}" class="mt-6 grid gap-5">
                @csrf
                <div class="field">
                    <label for="username" class="label">Username</label>
                    <input id="username" name="username" class="input" value="{{ old('username') }}" autocomplete="username" autofocus required maxlength="50" @error('username') aria-invalid="true" @enderror>
                </div>
                <div class="field">
                    <label for="password" class="label">Kata sandi</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" class="input pr-12" autocomplete="current-password" required>
                        <button type="button" class="btn btn-icon absolute right-1 top-1/2 -translate-y-1/2" data-toggle-password="password" aria-label="Tampilkan kata sandi"><i class="ph ph-eye"></i></button>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg w-full mt-1" data-loading-text="Memeriksa" @if (session('locked_seconds')) disabled @endif>
                    <i class="ph ph-sign-in"></i><span data-label>Masuk</span>
                </button>
                @if (session('locked_seconds'))
                    <p class="hint text-center">Terlalu banyak percobaan. Coba lagi dalam {{ ceil(session('locked_seconds') / 60) }} menit.</p>
                @endif
            </form>
        </div>
    </section>
</main>
</body>
</html>
