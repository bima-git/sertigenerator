<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Halaman Akun dan ganti kata sandi sendiri (PRD AU-06, Design System 3.2).
 */
class AccountController extends Controller
{
    /** GET /akun (account.edit) */
    public function edit()
    {
        return view('account.edit');
    }

    /** POST /akun/sandi (account.password) */
    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10), 'different:password_lama'],
        ], [
            'password_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'password_lama.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Ulangan kata sandi baru tidak sama.',
            'password.min' => 'Kata sandi baru minimal 10 karakter.',
            'password.different' => 'Kata sandi baru harus berbeda dari yang lama.',
        ]);

        $request->user()->update(['password' => $request->input('password')]); // di-hash oleh cast model

        return back()->with('success', 'Kata sandi berhasil diganti.');
    }
}
