<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Tampilkan daftar seluruh pengguna.
     * PRD PG-01 s.d. PG-03.
     */
    public function index(): View
    {
        $users = User::query()->orderBy('nama')->get();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Tampilkan formulir penambahan pengguna baru.
     */
    public function create(): View
    {
        return view('admin.users.form', [
            'user' => null,
        ]);
    }

    /**
     * Simpan pengguna baru dengan kata sandi sementara yang aman.
     * Kata sandi di-hash dan hanya ditampilkan sekali di session flash.
     * PRD PG-01, AU-01, AD-01.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Buat kata sandi sementara yang aman (12 karakter alfanumerik)
        $tempPassword = Str::password(12, symbols: false);

        $validated['password'] = Hash::make($tempPassword);
        $validated['aktif'] = $validated['aktif'] ?? true;
        $validated['gagal_login'] = 0;
        $validated['terkunci_sampai'] = null;

        $user = User::create($validated);

        // Catat log audit tanpa membocorkan kata sandi
        $this->auditLogger->log(
            $request->user(),
            'ubah_user',
            $user->id,
            [
                'tindakan' => 'tambah',
                'username' => $user->username,
                'nama' => $user->nama,
                'peran' => $user->peran,
                'aktif' => $user->aktif,
            ]
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Pengguna \"{$user->nama}\" ({$user->username}) berhasil ditambahkan.")
            ->with('temp_password', $tempPassword);
    }

    /**
     * Tampilkan formulir ubah data pengguna.
     */
    public function edit(int $id): View
    {
        $user = User::findOrFail($id);

        return view('admin.users.form', [
            'user' => $user,
        ]);
    }

    /**
     * Perbarui data pengguna dengan proteksi self-deactivation,
     * self-demotion, dan last active admin.
     * PRD PG-02, PG-03, AD-01.
     */
    public function update(UserRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validated();

        // 1. Proteksi akun sendiri: peran tetap admin, status tetap aktif
        if ($user->id === $request->user()->id) {
            $validated['peran'] = 'admin';
            $validated['aktif'] = true;
        } elseif ($user->isAdmin() && $user->aktif) {
            // 2. Proteksi admin aktif terakhir: tidak dapat dinonaktifkan atau diturunkan perannya
            $activeAdminCount = User::query()
                ->where('peran', 'admin')
                ->where('aktif', true)
                ->count();

            if ($activeAdminCount <= 1) {
                $validated['peran'] = 'admin';
                $validated['aktif'] = true;
            }
        }

        $user->update($validated);

        // Catat log audit tanpa data sensitif
        $this->auditLogger->log(
            $request->user(),
            'ubah_user',
            $user->id,
            [
                'tindakan' => 'ubah',
                'username' => $user->username,
                'nama' => $user->nama,
                'peran' => $user->peran,
                'aktif' => $user->aktif,
            ]
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Data pengguna \"{$user->nama}\" berhasil diperbarui.");
    }

    /**
     * Reset kata sandi pengguna dengan kata sandi sementara baru.
     * Mereset kegagalan login dan membuka kunci akun.
     * PRD PG-02, AU-04, AD-01.
     */
    public function reset(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Buat kata sandi sementara baru
        $tempPassword = Str::password(12, symbols: false);

        $user->password = Hash::make($tempPassword);
        $user->gagal_login = 0;
        $user->terkunci_sampai = null;
        $user->save();

        // Catat audit log tanpa mencatat kata sandi
        $this->auditLogger->log(
            $request->user(),
            'ubah_user',
            $user->id,
            [
                'tindakan' => 'reset_sandi',
                'username' => $user->username,
                'nama' => $user->nama,
            ]
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Kata sandi untuk \"{$user->nama}\" berhasil direset.")
            ->with('temp_password', $tempPassword);
    }
}
