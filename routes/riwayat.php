<?php

use App\Http\Controllers\Riwayat\MitraController;
use App\Http\Controllers\Riwayat\RiwayatDosenController;
use App\Http\Controllers\Riwayat\RiwayatKegiatanController;
use App\Models\Role;
use App\Services\AlurRiwayat;
use Illuminate\Support\Facades\Route;

$jenis = array_keys(AlurRiwayat::JENIS);

// A lecturer's own research and community service records.
Route::middleware(['auth', 'verified', 'role:'.Role::DOSEN])->group(function () use ($jenis) {
    Route::get('/riwayat/{jenis}', [RiwayatKegiatanController::class, 'index'])->whereIn('jenis', $jenis)->name('riwayat.index');
});

// The form is shared by lecturers and by LPPM and operators, who enter or
// correct records for any lecturer; AlurRiwayat decides who may change what.
Route::middleware(['auth', 'verified'])->prefix('riwayat/{jenis}')->whereIn('jenis', $jenis)->name('riwayat.')
    ->controller(RiwayatKegiatanController::class)->group(function () {
        Route::get('/buat', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/cek-ganda', 'cekGanda')->name('cek-ganda');
        Route::get('/{riwayat}/ubah', 'edit')->whereNumber('riwayat')->name('edit');
        Route::put('/{riwayat}', 'update')->whereNumber('riwayat')->name('update');
        Route::delete('/{riwayat}', 'destroy')->whereNumber('riwayat')->name('destroy');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/riwayat-berkas/{berkas}', [RiwayatKegiatanController::class, 'berkas'])->name('riwayat.berkas');
    Route::post('/mitra/usulan', [MitraController::class, 'usulan'])->name('mitra.usulan');
});

// Every lecturer's records for operators, LPPM and unit heads (only their unit's).
Route::middleware(['auth', 'verified', 'can:lihat-data-dosen'])->prefix('riwayat-dosen/{jenis}')->whereIn('jenis', $jenis)->name('riwayat-dosen.')->group(function () {
    Route::get('/', [RiwayatDosenController::class, 'index'])->name('index');
    Route::post('/{riwayat}/verifikasi', [RiwayatDosenController::class, 'verifikasi'])
        ->middleware('role:'.Role::ADMIN_LPPM.','.Role::OPERATOR)->whereNumber('riwayat')->name('verifikasi');
});

// LPPM keeps the partner register; partners are switched off, never deleted.
Route::middleware(['auth', 'verified', 'role:'.Role::ADMIN_LPPM.','.Role::OPERATOR])->group(function () {
    Route::resource('mitra', MitraController::class)->except(['show', 'destroy'])->parameters(['mitra' => 'mitra']);
    Route::post('/mitra/{mitra}/verifikasi', [MitraController::class, 'verifikasi'])->name('mitra.verifikasi');
});
