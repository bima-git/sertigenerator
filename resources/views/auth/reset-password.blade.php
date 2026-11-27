{{-- Reset kata sandi dari tautan email. Kontrak dengan modul Bima: POST route('password.update')
     menerima token, email, password, password_confirmation (Password broker Laravel), minimal 10 karakter (PRD AU-06).
     Tautan kedaluwarsa/terpakai dikembalikan broker sebagai galat `email`. --}}
@php
    $tautanRusak = $errors->has('email') && ! $errors->has('password');
@endphp
<x-auth-shell page-title="Buat kata sandi baru" title="Buat kata sandi baru"
    subtitle="Kata sandi minimal 10 karakter. Setelah disimpan, masuk lagi dengan kata sandi baru.">
    @if ($tautanRusak)
        <div class="alert alert-danger mt-5" role="alert">
            <i class="ph-fill ph-warning-octagon"></i>
            <div>
                <p>{{ $errors->first('email') }}</p>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="mt-1 inline-block font-semibold underline underline-offset-2">Minta tautan baru</a>
                @endif
            </div>
        </div>
    @endif

    @if (Route::has('password.update'))
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 grid gap-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label for="email" class="label">Email</label>
                <div class="relative">
                    <i class="ph ph-envelope-simple absolute left-4 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                    <input id="email" name="email" type="email" class="input login-input pl-11" value="{{ old('email', $email) }}"
                           autocomplete="username" inputmode="email" spellcheck="false" autocapitalize="off" required maxlength="255" @if ($email) readonly @endif>
                </div>
            </div>
            <div class="field">
                <label for="password" class="label">Kata sandi baru</label>
                <div class="relative">
                    <i class="ph ph-lock-simple absolute left-4 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                    <input id="password" name="password" type="password" class="input login-input pl-11 pr-12" autocomplete="new-password"
                           required minlength="10" autofocus aria-describedby="password-hint" @error('password') aria-invalid="true" @enderror>
                    <button type="button" class="btn btn-icon absolute right-1.5 top-1/2 -translate-y-1/2" data-toggle-password="password" aria-label="Tampilkan kata sandi"><i class="ph ph-eye"></i></button>
                </div>
                <p id="password-hint" class="hint">Minimal 10 karakter. Gunakan gabungan kata yang mudah kamu ingat.</p>
                @error('password') <p class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation" class="label">Ulangi kata sandi baru</label>
                <div class="relative">
                    <i class="ph ph-lock-simple absolute left-4 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input login-input pl-11 pr-12" autocomplete="new-password" required minlength="10">
                    <button type="button" class="btn btn-icon absolute right-1.5 top-1/2 -translate-y-1/2" data-toggle-password="password_confirmation" aria-label="Tampilkan kata sandi"><i class="ph ph-eye"></i></button>
                </div>
            </div>
            <button class="btn btn-primary btn-lg w-full mt-2" data-loading-text="Menyimpan">
                <i class="ph ph-check"></i><span data-label>Simpan Kata Sandi</span>
            </button>
        </form>
    @else
        <div class="alert alert-info mt-5" role="status">
            <i class="ph-fill ph-info"></i>
            <span>Reset kata sandi lewat email belum aktif. Hubungi Admin untuk dibuatkan kata sandi sementara.</span>
        </div>
    @endif

    <a href="{{ route('login') }}" class="btn btn-ghost w-full mt-3"><i class="ph ph-arrow-left"></i>Kembali ke halaman Masuk</a>
</x-auth-shell>
