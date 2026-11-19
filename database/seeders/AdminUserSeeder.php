<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $username = trim((string) env('ADMIN_USERNAME', 'admin'));
        $nama = trim((string) env('ADMIN_NAMA', 'Administrator'));
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($password === '') {
            throw new RuntimeException(
                'ADMIN_PASSWORD belum diatur. Tambahkan ADMIN_PASSWORD ke .env sebelum menjalankan AdminUserSeeder.'
            );
        }

        if ($username === '') {
            throw new RuntimeException(
                'ADMIN_USERNAME kosong. Isi ADMIN_USERNAME pada .env dengan username admin.'
            );
        }

        if (mb_strlen($username) > 50) {
            throw new RuntimeException(
                'ADMIN_USERNAME melebihi 50 karakter. Perpendek nilai ADMIN_USERNAME pada .env.'
            );
        }

        if ($nama === '') {
            throw new RuntimeException(
                'ADMIN_NAMA kosong. Isi ADMIN_NAMA pada .env dengan nama admin.'
            );
        }

        if (mb_strlen($nama) > 100) {
            throw new RuntimeException(
                'ADMIN_NAMA melebihi 100 karakter. Perpendek nilai ADMIN_NAMA pada .env.'
            );
        }

        $admin = User::query()->where('username', $username)->first();

        if ($admin !== null) {
            $this->command?->warn(
                "Akun dengan username \"{$username}\" sudah ada (peran: {$admin->peran}). Tidak ada perubahan."
            );

            return;
        }

        User::query()->create([
            'username' => $username,
            'nama' => $nama,
            'password' => Hash::make($password),
            'peran' => 'admin',
            'aktif' => true,
            'gagal_login' => 0,
            'terkunci_sampai' => null,
        ]);

        $this->command?->info("Akun admin \"{$username}\" berhasil dibuat.");
    }
}
