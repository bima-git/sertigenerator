<?php

use App\Http\Controllers\CertificatePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pratinjau', [CertificatePreviewController::class, 'index'])->name('pratinjau');
Route::get('/pratinjau/pdf', [CertificatePreviewController::class, 'pdf'])->name('pratinjau.pdf');
