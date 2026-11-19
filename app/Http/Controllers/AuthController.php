<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Masuk dan keluar (PRD AU-01 sampai AU-05, Design System 3.1 dan 3.2).
 */
class AuthController extends Controller
{
    private const MAX_GAGAL = 5;      // AU-04: lima kegagalan berturut-turut
    private const KUNCI_MENIT = 10;   // AU-04: dikunci 10 menit

    public function __construct(private AuditLogger $audit) {}

    /** GET /masuk (login) */
    public function create()
    {
        return view('auth.login');
    }

    /** POST /masuk (login.attempt), dibatasi throttle 10 per menit per IP di rute. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::query()->where('username', $data['username'])->first();

        if ($user?->terkunci_sampai?->isFuture()) {
            $detik = (int) ceil(now()->diffInSeconds($user->terkunci_sampai));
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan. Coba lagi dalam '.max(1, (int) ceil($detik / 60)).' menit.',
            ])->redirectTo(route('login'));
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            $this->recordFailure($user, $data['username']);
            throw ValidationException::withMessages(['username' => 'Username atau kata sandi tidak cocok.'])
                ->redirectTo(route('login'));
        }

        if (! $user->aktif) {
            throw ValidationException::withMessages(['username' => 'Akun ini nonaktif. Hubungi admin.'])
                ->redirectTo(route('login'));
        }

        $user->forceFill(['gagal_login' => 0, 'terkunci_sampai' => null])->save();
        Auth::login($user);
        $request->session()->regenerate();
        $this->audit->log($user, 'masuk');

        return redirect()->intended(route('issue.form'));
    }

    /** POST /keluar (logout) */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda sudah keluar.');
    }

    private function recordFailure(?User $user, string $username): void
    {
        $this->audit->log($user, 'gagal_masuk', null, ['username' => $username]);

        if (! $user) {
            return;
        }

        $gagal = $user->gagal_login + 1;
        $user->forceFill($gagal >= self::MAX_GAGAL
            ? ['gagal_login' => 0, 'terkunci_sampai' => now()->addMinutes(self::KUNCI_MENIT)]
            : ['gagal_login' => $gagal])->save();
    }
}
