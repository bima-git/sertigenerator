<?php

use App\Support\UiDummy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman antarmuka (modul Giska)
|--------------------------------------------------------------------------
| Nama rute mengikuti Design System bagian 3. Closure di bawah memakai
| UiDummy sampai controller Akmal/Bima digabung; ganti closure dengan
| controller, view tidak perlu diubah. Rute POST (masuk, terbitkan,
| pratinjau, batalkan, simpan) milik modul backend.
*/

Route::view('/masuk', 'auth.login')->name('login');

Route::redirect('/', '/terbitkan')->name('home');

Route::get('/terbitkan', fn () => view('issue.create', [
    'events' => UiDummy::events(),
]))->name('issue.form');

Route::get('/riwayat', function (Request $request) {
    $q = mb_strtolower(trim((string) $request->query('q')));
    $jenis = (array) $request->query('jenis', []);
    $status = (array) $request->query('status', []);

    $all = UiDummy::certificates();
    $rows = array_values(array_filter($all, fn ($c) => ($q === '' || str_contains(mb_strtolower($c['nama_peserta'].' '.$c['nomor_lengkap']), $q))
        && (! $jenis || in_array($c['jenis'], $jenis, true))
        && (! $status || in_array($c['status'], $status, true))));

    return view('history.index', [
        'certificates' => $rows,
        'stats' => [
            'total' => count($all),
            'seminar' => count(array_filter($all, fn ($c) => $c['jenis'] === 'seminar')),
            'bootcamp' => count(array_filter($all, fn ($c) => $c['jenis'] === 'bootcamp')),
            'batal' => count(array_filter($all, fn ($c) => $c['status'] === 'dibatalkan')),
        ],
        'monthly' => UiDummy::monthly(),
    ]);
})->name('history.index');

Route::get('/riwayat/{id}', fn (int $id) => view('history.show', [
    'c' => UiDummy::certificate($id) ?? abort(404),
]))->whereNumber('id')->name('history.show');

Route::view('/akun', 'account.edit')->name('account.edit');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/kegiatan', fn () => view('admin.events.index', ['events' => UiDummy::events()]))->name('events.index');
    Route::get('/kegiatan/baru', fn () => view('admin.events.form', ['event' => null]))->name('events.create');
    Route::get('/kegiatan/{id}/ubah', fn (int $id) => view('admin.events.form', [
        'event' => collect(UiDummy::events())->firstWhere('id', $id) ?? abort(404),
    ]))->name('events.edit');

    Route::get('/pengguna', fn () => view('admin.users.index', ['users' => UiDummy::users()]))->name('users.index');
    Route::get('/pengguna/baru', fn () => view('admin.users.form', ['user' => null]))->name('users.create');
    Route::get('/pengguna/{id}/ubah', fn (int $id) => view('admin.users.form', [
        'user' => collect(UiDummy::users())->firstWhere('id', $id) ?? abort(404),
    ]))->name('users.edit');

    Route::get('/audit', fn () => view('admin.audit', ['logs' => UiDummy::audit()]))->name('audit');
    Route::view('/uji-layout', 'admin.layout-test')->name('layout.test');
});
