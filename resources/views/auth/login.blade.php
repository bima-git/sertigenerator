<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Generator Sertifikat XCODE</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05); width: 100%; max-width: 420px; padding: 2.25rem 2rem; }
        .logo-box { text-align: center; margin-bottom: 2rem; }
        .logo-badge { display: inline-block; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 1.125rem; padding: 0.35rem 0.85rem; border-radius: 0.375rem; letter-spacing: 0.05em; }
        .title { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-top: 0.75rem; }
        .subtitle { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .form-group { margin-bottom: 1.25rem; }
        .label { display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem; }
        .input { width: 100%; height: 44px; padding: 0 0.875rem; border: 1px solid #cbd5e1; border-radius: 0.375rem; font-size: 0.95rem; color: #0f172a; transition: border-color 0.15s, box-shadow 0.15s; outline: none; }
        .input:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15); }
        .input-error { border-color: #ef4444 !important; }
        .error-text { font-size: 0.8rem; color: #ef4444; margin-top: 0.35rem; font-weight: 500; }
        .alert-error { background-color: #fef2f2; border: 1px solid #fee2e2; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 0.375rem; font-size: 0.875rem; margin-bottom: 1.25rem; }
        .btn-submit { width: 100%; height: 50px; background-color: #0284c7; color: #ffffff; border: none; border-radius: 0.375rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.15s, transform 0.05s; margin-top: 0.5rem; }
        .btn-submit:hover { background-color: #0369a1; }
        .btn-submit:active { transform: scale(0.99); }
        .password-wrapper { position: relative; }
        .password-toggle { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; font-size: 0.75rem; font-weight: 600; cursor: pointer; padding: 0.25rem; }
        .footer-note { text-align: center; font-size: 0.8rem; color: #94a3b8; margin-top: 1.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-box">
            <span class="logo-badge">XCODE</span>
            <h1 class="title">Sistem Sertifikat</h1>
            <p class="subtitle">Masuk untuk menerbitkan dan mengelola sertifikat</p>
        </div>

        @if ($errors->any())
            <div class="alert-error" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <div class="form-group">
                <label for="username" class="label">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="input @error('username') input-error @enderror"
                    placeholder="Masukkan username"
                />
            </div>

            <div class="form-group">
                <label for="password" class="label">Kata Sandi</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="input @error('password') input-error @enderror"
                        placeholder="Masukkan kata sandi"
                    />
                    <button type="button" class="password-toggle" id="togglePasswordBtn" aria-label="Tampilkan kata sandi">
                        Lihat
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Masuk
            </button>
        </form>

        <p class="footer-note">PT Teknologi Kode Indonesia &copy; 2026</p>
    </div>

    <script>
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function () {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleBtn.textContent = 'Sembunyikan';
                    toggleBtn.setAttribute('aria-label', 'Sembunyikan kata sandi');
                } else {
                    passwordInput.type = 'password';
                    toggleBtn.textContent = 'Lihat';
                    toggleBtn.setAttribute('aria-label', 'Tampilkan kata sandi');
                }
            });
        }
    </script>
</body>
</html>
