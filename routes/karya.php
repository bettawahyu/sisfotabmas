<?php

use App\Http\Controllers\Karya\HkiController;
use App\Http\Controllers\Karya\PatenController;
use App\Http\Controllers\Karya\PublikasiController;
use App\Http\Controllers\Karya\VerifikasiKaryaController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

// Lecturers keep their own publications, HKI and patents.
Route::middleware(['auth', 'verified', 'role:'.Role::DOSEN])->prefix('karya')->name('karya.')->group(function () {
    Route::resource('publikasi', PublikasiController::class)->except('show')->parameters(['publikasi' => 'karya'])->whereNumber('karya');
    Route::resource('hki', HkiController::class)->except('show')->parameters(['hki' => 'karya'])->whereNumber('karya');
    Route::resource('paten', PatenController::class)->except('show')->parameters(['paten' => 'karya'])->whereNumber('karya');
});

// The kaprodi checks first, then LPPM marks the entry valid.
Route::middleware(['auth', 'verified', 'role:'.Role::PIMPINAN_UNIT.','.Role::ADMIN_LPPM])->prefix('verifikasi-karya')->name('verifikasi-karya.')->group(function () {
    Route::get('/', [VerifikasiKaryaController::class, 'index'])->name('index');
    Route::post('/{jenis}/{karya}', [VerifikasiKaryaController::class, 'store'])
        ->whereIn('jenis', ['publikasi', 'hki', 'paten'])->whereNumber('karya')->name('store');
});
