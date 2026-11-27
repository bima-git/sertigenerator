<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Lupa dan reset kata sandi lewat email (rencana login email 2026-11-27).
 * Halaman GET-nya ada di routes/web.php (password.request, password.reset).
 */
class PasswordResetController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * POST /lupa-sandi (password.email).
     * Selalu menjawab sama, terdaftar atau tidak, supaya daftar email akun tidak bisa ditebak.
     * Akun nonaktif tidak dikirimi tautan.
     */
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email', 'max:255']],
            ['email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.'],
        );

        Password::sendResetLink([
            'email' => mb_strtolower(trim($request->input('email'))),
            'aktif' => true,
        ]);

        return back()->with('status', 'terkirim');
    }

    /** POST /reset-sandi (password.update). */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Ulangi kata sandi baru dengan isi yang sama.',
            'password.min' => 'Kata sandi minimal 10 karakter.',
        ]);

        $status = Password::reset(
            [
                'email' => mb_strtolower(trim($request->input('email'))),
                'aktif' => true,
                'token' => $request->input('token'),
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function (User $user, string $password) {
                // Kata sandi baru juga membuka kunci akibat salah kata sandi berulang (AU-04).
                $user->forceFill(['password' => $password, 'gagal_login' => 0, 'terkunci_sampai' => null])->save();
                $this->auditLogger->log($user->id, 'reset_sandi', $user->id, ['cara' => 'email']);
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Kata sandi berhasil diganti. Silakan masuk dengan kata sandi baru.');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Tautan reset tidak berlaku lagi (kedaluwarsa, sudah dipakai, atau email tidak cocok).',
        ]);
    }
}
