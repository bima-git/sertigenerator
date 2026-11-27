@php
    // Masa transisi login email (rencana tim): aktif setelah LoginRequest Bima menerima email.
    // Nama field tetap "username" supaya kontrak AuthController tidak berubah.
    $pakaiEmail = config('auth.login_email');
    $bisaReset = Route::has('password.request') && Route::has('password.email');
@endphp
<x-auth-shell page-title="Masuk" title="Masuk ke Sistem"
    :subtitle="$pakaiEmail ? 'Masukkan email atau username dan kata sandi untuk mengakses Sistem Sertifikat XCODE.' : 'Masukkan username dan kata sandi untuk mengakses Sistem Sertifikat XCODE.'">
    @if (session('status'))
        <div class="alert alert-info mt-5" role="status"><i class="ph-fill ph-info"></i><span>{{ session('status') }}</span></div>
    @endif
    @error('username')
        <div class="alert alert-danger mt-5" role="alert"><i class="ph-fill ph-warning-octagon"></i><span>{{ $message }}</span></div>
    @enderror

    <form method="POST" action="{{ url('/masuk') }}" class="mt-6 grid gap-4">
        @csrf
        <div class="field">
            <label for="username" class="label">{{ $pakaiEmail ? 'Email atau username' : 'Username' }}</label>
            <div class="relative">
                <i class="ph {{ $pakaiEmail ? 'ph-envelope-simple' : 'ph-user' }} absolute left-4 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                <input id="username" name="username" class="input login-input pl-11" value="{{ old('username') }}" autocomplete="username"
                       autofocus required maxlength="{{ $pakaiEmail ? 255 : 50 }}" @if ($pakaiEmail) inputmode="email" spellcheck="false" autocapitalize="off" @endif
                       @error('username') aria-invalid="true" @enderror>
            </div>
        </div>
        <div class="field">
            <div class="flex items-baseline justify-between gap-3">
                <label for="password" class="label">Kata sandi</label>
                @if ($bisaReset)
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand hover:underline">Lupa kata sandi?</a>
                @endif
            </div>
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
</x-auth-shell>
