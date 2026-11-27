{{-- Lupa kata sandi. Kontrak dengan modul Bima: POST route('password.email') menerima `email`,
     lalu kembali dengan session('status') atau galat `email` (Password broker Laravel).
     Selama rute itu belum ada, halaman hanya mengarahkan ke Admin. --}}
<x-auth-shell page-title="Lupa kata sandi" title="Lupa kata sandi"
    :subtitle="Route::has('password.email') ? 'Masukkan email akunmu. Kami kirim tautan untuk membuat kata sandi baru.' : 'Kata sandi baru dibuatkan oleh Admin.'">
    @if (session('status'))
        {{-- Pesan sama untuk email terdaftar maupun tidak, supaya daftar akun tidak bocor. --}}
        <div class="alert alert-success mt-5" role="status">
            <i class="ph-fill ph-envelope-simple-open"></i>
            <span>Jika email itu terdaftar, tautan reset sudah dikirim. Periksa kotak masuk dan folder spam. Tautan berlaku 60 menit.</span>
        </div>
    @endif

    @if (Route::has('password.email'))
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 grid gap-4">
            @csrf
            <div class="field">
                <label for="email" class="label">Email</label>
                <div class="relative">
                    <i class="ph ph-envelope-simple absolute left-4 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                    <input id="email" name="email" type="email" class="input login-input pl-11" value="{{ old('email') }}"
                           autocomplete="email" inputmode="email" spellcheck="false" autocapitalize="off" autofocus required maxlength="255"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                </div>
                @error('email') <p id="email-error" class="error"><i class="ph-fill ph-warning-circle"></i>{{ $message }}</p> @enderror
            </div>
            <button class="btn btn-primary btn-lg w-full mt-2" data-loading-text="Mengirim">
                <i class="ph ph-paper-plane-tilt"></i><span data-label>Kirim Tautan Reset</span>
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
