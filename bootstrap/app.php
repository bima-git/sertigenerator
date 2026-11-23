<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['active' => \App\Http\Middleware\EnsureUserIsActive::class]);
        $middleware->redirectUsersTo('/terbitkan');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Nomor ganda adalah kesalahan isian, bukan galat server: tampilkan pesannya ke operator.
        $exceptions->render(fn (\App\Exceptions\DuplicateNumberException $e, $request) => $request->expectsJson()
            ? response()->json(['message' => $e->getMessage()], 409)
            : back()->withInput()->with('error', $e->getMessage()));

        // Token form kedaluwarsa (tab lama, sesi habis, atau akun berganti di tab lain).
        // Keluar tetap dijalankan; form lain kembali ke halamannya dengan pesan, bukan halaman 419.
        // Laravel sudah mengubah TokenMismatchException menjadi HttpException 419 sebelum callback ini.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null; // Biarkan 419 JSON; issue-form.js sudah menampilkan pesannya.
            }
            if ($request->is('keluar')) {
                auth()->guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Kamu sudah keluar.');
            }

            return back()->withInput($request->except(['password', 'password_lama', 'password_confirmation', '_token']))
                ->with($request->is('masuk') ? 'status' : 'error', 'Halaman sudah terlalu lama terbuka, jadi data belum terkirim. Silakan kirim lagi.');
        });
    })->create();
