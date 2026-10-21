<?php

use App\Http\Controllers\Karya\HkiController;
use App\Http\Controllers\Karya\KaryaDosenController;
use App\Http\Controllers\Karya\PatenController;
use App\Http\Controllers\Karya\PublikasiController;
use App\Models\Role;
use App\Services\AlurKarya;
use Illuminate\Support\Facades\Route;

// Lecturers keep their own publications, HKI and patents.
Route::middleware(['auth', 'verified', 'role:'.Role::DOSEN])->prefix('karya')->name('karya.')->group(function () {
    Route::resource('publikasi', PublikasiController::class)->except('show')->parameters(['publikasi' => 'karya'])->whereNumber('karya');
    Route::resource('hki', HkiController::class)->except('show')->parameters(['hki' => 'karya'])->whereNumber('karya');
    Route::resource('paten', PatenController::class)->except('show')->parameters(['paten' => 'karya'])->whereNumber('karya');
});

// Every lecturer's entries for operators, LPPM and unit heads (only their unit's);
// LPPM or an operator marks each entry valid.
Route::middleware(['auth', 'verified', 'can:lihat-data-dosen'])->prefix('karya-dosen')->name('karya-dosen.')->group(function () {
    Route::get('/', [KaryaDosenController::class, 'index'])->name('index');
    Route::post('/{jenis}/{karya}', [KaryaDosenController::class, 'verifikasi'])
        ->middleware('role:'.Role::ADMIN_LPPM.','.Role::OPERATOR)
        ->whereIn('jenis', array_keys(AlurKarya::JENIS))->whereNumber('karya')->name('verifikasi');
});

// Older notifications link to the verification page this replaced.
Route::redirect('/verifikasi-karya', '/karya-dosen');
