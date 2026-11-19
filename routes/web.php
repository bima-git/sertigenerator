<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi Pengguna (Sesuai PRD Section 4.1 & Design System 3.1 - 3.2)
|--------------------------------------------------------------------------
*/
Route::get('/masuk', [AuthController::class, 'create'])->name('login');
Route::post('/masuk', [AuthController::class, 'store'])->name('login.attempt');
Route::post('/keluar', [AuthController::class, 'destroy'])->name('logout');

// REST API Login Endpoints (Tanpa CSRF via api/* exception)
Route::post('/api/login', [AuthController::class, 'store'])->name('api.login');
Route::post('/api/logout', [AuthController::class, 'destroy'])->name('api.logout');
Route::get('/api/me', [AuthController::class, 'me'])->name('api.me');

/*
|--------------------------------------------------------------------------
| Halaman Utama: Pengalihan sesuai status sesi (Design System 3.2 & 3.5)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('issue.store');
    }
    return redirect()->route('login');
})->name('home');

/*
|--------------------------------------------------------------------------
| API Endpoints untuk Form Penerbitan (Modul Bima & Giska)
| Sesuai Design System Bagian 3.3
|--------------------------------------------------------------------------
*/
Route::get('/api/kegiatan', [IssueController::class, 'events'])->name('api.events');
Route::get('/api/nomor', [IssueController::class, 'suggest'])->name('api.number');

/*
|--------------------------------------------------------------------------
| Aksi Penerbitan dan Pratinjau Sertifikat (Modul Bima)
| Sesuai PRD Bagian 4.3 dan Design System Bagian 3.3
|--------------------------------------------------------------------------
*/
Route::post('/pratinjau', [IssueController::class, 'preview'])->name('issue.preview');
Route::post('/terbitkan', [IssueController::class, 'store'])->name('issue.store');

/*
|--------------------------------------------------------------------------
| Aksi Riwayat, Unduh Ulang & Pembatalan Sertifikat (Modul Bima & Akmal)
| Sesuai PRD Bagian 4.6 dan Design System Bagian 3.3 & 3.4
|--------------------------------------------------------------------------
*/
Route::get('/riwayat/{id}/pdf', [HistoryController::class, 'download'])->name('history.pdf');
Route::post('/riwayat/{id}/batalkan', [HistoryController::class, 'cancel'])->name('history.cancel');
