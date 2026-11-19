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
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Nomor ganda adalah kesalahan isian, bukan galat server: tampilkan pesannya ke operator.
        $exceptions->render(fn (\App\Exceptions\DuplicateNumberException $e, $request) => $request->expectsJson()
            ? response()->json(['message' => $e->getMessage()], 409)
            : back()->withInput()->with('error', $e->getMessage()));
    })->create();
