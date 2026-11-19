<?php

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IssueController;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman (modul Giska) dan aksi penerbitan (modul Bima)
|--------------------------------------------------------------------------
| Nama rute mengikuti Design System bagian 3. Halaman admin masih closure
| baca-saja; aksi simpan/ubah dan autentikasi menunggu modul Akmal.
*/

Route::view('/masuk', 'auth.login')->name('login');

/*
 * Selain local/testing, semua rute wajib login dan /admin wajib peran admin.
 * ponytail: pengecualian local/testing hanya sampai POST /masuk (modul Akmal) ada;
 * setelah itu pakai middleware tanpa syarat dan tambahkan actingAs di test API.
 */
$open = app()->environment('local', 'testing');

Route::middleware($open ? [] : ['auth'])->group(function () use ($open) {
Route::redirect('/', '/terbitkan')->name('home');

Route::get('/terbitkan', [IssueController::class, 'create'])->name('issue.form');
Route::get('/riwayat', [HistoryController::class, 'index'])->name('history.index');
Route::get('/riwayat/{id}', [HistoryController::class, 'show'])->whereNumber('id')->name('history.show');
Route::view('/akun', 'account.edit')->name('account.edit');

/* API form penerbitan (Design System 3.3) */
Route::get('/api/kegiatan', [IssueController::class, 'events'])->name('api.events');
Route::get('/api/nomor', [IssueController::class, 'suggest'])->name('api.number');

/* Aksi penerbitan, pratinjau, unduh ulang, pembatalan */
Route::post('/pratinjau', [IssueController::class, 'preview'])->name('issue.preview');
Route::post('/terbitkan', [IssueController::class, 'store'])->name('issue.store');
Route::get('/riwayat/{id}/pdf', [HistoryController::class, 'download'])->name('history.pdf');
Route::post('/riwayat/{id}/batalkan', [HistoryController::class, 'cancel'])->name('history.cancel');

Route::prefix('admin')->name('admin.')->middleware($open ? [] : ['can:admin'])->group(function () {
    Route::get('/kegiatan', fn (Request $r) => view('admin.events.index', [
        'events' => Event::query()
            ->when($r->query('jenis'), fn ($q, $j) => $q->where('jenis', $j))
            ->when($r->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('kode', 'like', "%{$s}%")->orWhere('judul', 'like', "%{$s}%")))
            ->orderByDesc('aktif')->orderByRaw('CAST(kode AS UNSIGNED)')->get(),
    ]))->name('events.index');
    Route::get('/kegiatan/baru', fn () => view('admin.events.form', ['event' => null]))->name('events.create');
    Route::get('/kegiatan/{id}/ubah', fn (int $id) => view('admin.events.form', ['event' => Event::findOrFail($id)]))->name('events.edit');

    Route::get('/pengguna', fn () => view('admin.users.index', ['users' => User::orderBy('nama')->get()]))->name('users.index');
    Route::get('/pengguna/baru', fn () => view('admin.users.form', ['user' => null]))->name('users.create');
    Route::get('/pengguna/{id}/ubah', fn (int $id) => view('admin.users.form', ['user' => User::findOrFail($id)]))->name('users.edit');

    Route::get('/audit', fn (Request $r) => view('admin.audit', [
        'logs' => AuditLog::with('user')
            ->when($r->query('aksi'), fn ($q, $a) => $q->whereIn('aksi', (array) $a))
            ->latest('created_at')->paginate(25)->withQueryString(),
    ]))->name('audit');
    Route::view('/uji-layout', 'admin.layout-test')->name('layout.test');
});
});
