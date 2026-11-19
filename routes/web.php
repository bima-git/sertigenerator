<?php

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
