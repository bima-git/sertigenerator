<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Menampilkan form login (GET /masuk).
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('issue.store');
        }

        return view('auth.login');
    }

    /**
     * Memproses percobaan login (POST /masuk atau POST /api/login).
     *
     * Aturan Bisnis (PRD Section 4.1 & Design System Bagian 10):
     * - AU-01: Verifikasi kata sandi dengan Hash::check
     * - AU-03: Sesi diregenerasi via session()->regenerate()
     * - AU-04: 5 kegagalan berturut-turut mengunci akun selama 10 menit
     * - AU-05: Akun nonaktif tidak diizinkan masuk
     * - AD-01: Pencatatan jejak audit (masuk, gagal_masuk)
     */
    public function store(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $username = $request->input('username');
        $password = $request->input('password');

        /** @var User|null $user */
        $user = User::query()->where('username', $username)->first();

        // 1. Periksa apakah akun sedang dalam masa penguncian (AU-04)
        if ($user && $user->terkunci_sampai !== null) {
            $lockExpiry = Carbon::parse($user->terkunci_sampai);
            if ($lockExpiry->isFuture()) {
                $sisaMenit = max(1, (int) ceil(now()->diffInSeconds($lockExpiry) / 60));

                $this->auditLogger->log($user->id, 'gagal_masuk', null, [
                    'alasan'     => 'Akun sedang terkunci',
                    'sisa_menit' => $sisaMenit,
                ]);

                $errorMessage = "Terlalu banyak percobaan. Coba lagi dalam {$sisaMenit} menit.";

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => $errorMessage,
                        'errors'  => ['username' => [$errorMessage]],
                    ], 429);
                }

                return back()->withErrors(['username' => $errorMessage])->withInput($request->only('username'));
            }

            // Masa kunci telah habis, reset counter
            $user->update([
                'gagal_login'     => 0,
                'terkunci_sampai' => null,
            ]);
        }

        // 2. Periksa kecocokan kredensial (AU-01)
        if (! $user || ! Hash::check($password, $user->password)) {
            if ($user) {
                $user->increment('gagal_login');
                $user->refresh();

                // Kunci akun jika sudah mencapai 5 kali kegagalan
                if ($user->gagal_login >= 5) {
                    $user->update([
                        'terkunci_sampai' => now()->addMinutes(10),
                    ]);

                    $this->auditLogger->log($user->id, 'gagal_masuk', null, [
                        'alasan' => 'Akun terkunci karena 5 kali salah kata sandi',
                    ]);

                    $lockMessage = 'Terlalu banyak percobaan. Coba lagi dalam 10 menit.';

                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'message' => $lockMessage,
                            'errors'  => ['username' => [$lockMessage]],
                        ], 429);
                    }

                    return back()->withErrors(['username' => $lockMessage])->withInput($request->only('username'));
                }

                $this->auditLogger->log($user->id, 'gagal_masuk', null, [
                    'alasan'      => 'Kata sandi salah',
                    'percobaan_ke' => $user->gagal_login,
                ]);
            } else {
                $this->auditLogger->log(null, 'gagal_masuk', null, [
                    'username' => $username,
                    'alasan'   => 'Username tidak terdaftar',
                ]);
            }

            $errorMessage = 'Username atau kata sandi tidak cocok.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $errorMessage,
                    'errors'  => ['username' => [$errorMessage]],
                ], 422);
            }

            return back()->withErrors(['username' => $errorMessage])->withInput($request->only('username'));
        }

        // 3. Periksa status keaktifan akun (AU-05)
        if (! $user->aktif) {
            $this->auditLogger->log($user->id, 'gagal_masuk', null, [
                'alasan' => 'Akun dinonaktifkan',
            ]);

            $inactiveMessage = 'Akun Anda telah dinonaktifkan. Hubungi administrator.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $inactiveMessage,
                    'errors'  => ['username' => [$inactiveMessage]],
                ], 403);
            }

            return back()->withErrors(['username' => $inactiveMessage])->withInput($request->only('username'));
        }

        // 4. Kredensial valid & akun aktif: Sukses login
        $user->update([
            'gagal_login'     => 0,
            'terkunci_sampai' => null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $this->auditLogger->log($user->id, 'masuk');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success'  => true,
                'message'  => 'Berhasil masuk.',
                'user'     => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'nama'     => $user->nama,
                    'peran'    => $user->peran,
                ],
                'redirect' => '/terbitkan',
            ]);
        }

        return redirect()->intended('/terbitkan');
    }

    /**
     * Memproses logout (POST /keluar atau POST /api/logout).
     *
     * Aturan Bisnis (PRD Section 4.1 AU-02):
     * - Menghapus sesi dan meregenerasi token CSRF
     */
    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->auditLogger->log($user->id, 'keluar');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil keluar.',
            ]);
        }

        return redirect()->route('login');
    }

    /**
     * Mengembalikan data pengguna yang sedang login saat ini (GET /api/me).
     */
    public function me(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'id'       => $user->id,
            'username' => $user->username,
            'nama'     => $user->nama,
            'peran'    => $user->peran,
            'aktif'    => (bool) $user->aktif,
        ]);
    }
}
