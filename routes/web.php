<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificatePreviewController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\PasswordResetController;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi (AuthController modul Bima, PRD 4.1, Design System 3.1 - 3.2)
|--------------------------------------------------------------------------
*/
// REST API login. Tetap dijaga CSRF: klien kirim header X-XSRF-TOKEN dari cookie XSRF-TOKEN. /api/me menjawab 401 sendiri bila belum masuk.
Route::post('/api/login', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('api.login');
Route::post('/api/logout', [AuthController::class, 'destroy'])->name('api.logout');
Route::get('/api/me', [AuthController::class, 'me'])->name('api.me');

/*
|--------------------------------------------------------------------------
| Halaman (modul Giska) dan aksi penerbitan (modul Bima)
|--------------------------------------------------------------------------
| Nama rute mengikuti Design System bagian 3. Kegiatan dan Pengguna modul Akmal.
*/

/* Tanpa sesi (Design System 3.1) */
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('login.attempt');

    // Lupa/reset kata sandi lewat email. Nama rute mengikuti bawaan Laravel supaya
    // tautan di email Password broker langsung ke sini.
    Route::view('/lupa-sandi', 'auth.forgot-password')->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-sandi/{token}', fn (Request $r, string $token) => view('auth.reset-password', [
        'token' => $token,
        'email' => (string) $r->query('email', ''),
    ]))->name('password.reset');
    Route::post('/reset-sandi', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
});

/* Semua halaman lain wajib sesi aktif dan akun aktif (PRD AU-05) */
Route::middleware(['auth', 'active'])->group(function () {
Route::redirect('/', '/terbitkan')->name('home');
Route::post('/keluar', [AuthController::class, 'destroy'])->name('logout');

Route::get('/terbitkan', [IssueController::class, 'create'])->name('issue.form');
Route::get('/riwayat', [HistoryController::class, 'index'])->name('history.index');
Route::get('/riwayat/{id}', [HistoryController::class, 'show'])->whereNumber('id')->name('history.show');
Route::get('/akun', [AccountController::class, 'edit'])->name('account.edit');
Route::post('/akun/sandi', [AccountController::class, 'password'])->name('account.password');

/* API form penerbitan (Design System 3.3) */
Route::get('/api/kegiatan', [IssueController::class, 'events'])->name('api.events');
Route::get('/api/nomor', [IssueController::class, 'suggest'])->name('api.number');
Route::get('/api/nama-sama', [IssueController::class, 'sameName'])->name('api.same-name');
Route::post('/api/cek-teks', [IssueController::class, 'checkText'])->name('api.text-check');

/* Aksi penerbitan, pratinjau, unduh ulang, pembatalan */
Route::post('/pratinjau', [IssueController::class, 'preview'])->name('issue.preview');
Route::post('/terbitkan', [IssueController::class, 'store'])->name('issue.store');
Route::get('/riwayat/{id}/pdf', [HistoryController::class, 'download'])->whereNumber('id')->name('history.pdf');
Route::post('/riwayat/{id}/batalkan', [HistoryController::class, 'cancel'])->whereNumber('id')->name('history.cancel');

Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function () {
    Route::get('/kegiatan', [EventController::class, 'index'])->name('events.index');
    Route::get('/kegiatan/baru', [EventController::class, 'create'])->name('events.create');
    Route::post('/kegiatan/baru', [EventController::class, 'store'])->name('events.store');
    Route::get('/kegiatan/{id}/ubah', [EventController::class, 'edit'])->whereNumber('id')->name('events.edit');
    Route::post('/kegiatan/{id}/ubah', [EventController::class, 'update'])->whereNumber('id')->name('events.update');
    Route::post('/kegiatan/{id}/aktif', [EventController::class, 'toggle'])->whereNumber('id')->name('events.toggle');

    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::get('/pengguna/baru', [UserController::class, 'create'])->name('users.create');
    Route::post('/pengguna/baru', [UserController::class, 'store'])->name('users.store');
    Route::get('/pengguna/{id}/ubah', [UserController::class, 'edit'])->whereNumber('id')->name('users.edit');
    Route::post('/pengguna/{id}/ubah', [UserController::class, 'update'])->whereNumber('id')->name('users.update');
    Route::post('/pengguna/{id}/reset-sandi', [UserController::class, 'reset'])->whereNumber('id')->name('users.reset');

    Route::get('/audit', fn (Request $r) => view('admin.audit', [
        'logs' => AuditLog::with('user')
            ->when($r->query('aksi'), fn ($q, $a) => $q->whereIn('aksi', (array) $a))
            ->latest('created_at')->paginate(25)->withQueryString(),
    ]))->name('audit');
    // Uji Layout dikerjakan Ifan di /pratinjau (PDF contoh, garis bantu, tanda air).
    Route::redirect('/uji-layout', '/pratinjau')->name('layout.test');
});

/*
|--------------------------------------------------------------------------
| Pratinjau desain sertifikat (Modul Ifan)
| Hanya memakai data contoh, tidak mengakses basis data penerbitan.
| Khusus admin: ini halaman Uji Layout (Design System 3.4).
|--------------------------------------------------------------------------
*/
Route::middleware('can:admin')->group(function () {
    Route::get('/pratinjau', [CertificatePreviewController::class, 'index'])->name('pratinjau');
    Route::get('/pratinjau/pdf', [CertificatePreviewController::class, 'pdf'])->name('pratinjau.pdf');
});
});
