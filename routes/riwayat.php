<?php

use App\Http\Controllers\Riwayat\ImporRiwayatController;
use App\Http\Controllers\Riwayat\MitraController;
use App\Http\Controllers\Riwayat\RekapRiwayatController;
use App\Http\Controllers\Riwayat\RiwayatDosenController;
use App\Http\Controllers\Riwayat\RiwayatKegiatanController;
use App\Models\Role;
use App\Services\AlurRiwayat;
use Illuminate\Support\Facades\Route;

$jenis = array_keys(AlurRiwayat::JENIS);

// A lecturer's own research and community service records.
Route::middleware(['auth', 'verified', 'role:'.Role::DOSEN])->group(function () use ($jenis) {
    Route::get('/riwayat/{jenis}', [RiwayatKegiatanController::class, 'index'])->whereIn('jenis', $jenis)->name('riwayat.index');
    Route::get('/riwayat/{jenis}/ekspor', [RiwayatKegiatanController::class, 'ekspor'])->whereIn('jenis', $jenis)->name('riwayat.ekspor');
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
    Route::get('/ekspor', [RiwayatDosenController::class, 'ekspor'])->name('ekspor');
    Route::post('/{riwayat}/verifikasi', [RiwayatDosenController::class, 'verifikasi'])
        ->middleware('role:'.Role::ADMIN_LPPM.','.Role::OPERATOR)->whereNumber('riwayat')->name('verifikasi');
});

// LPPM and operators load old records from Excel.
Route::middleware(['auth', 'verified', 'role:'.Role::ADMIN_LPPM.','.Role::OPERATOR])->prefix('riwayat-dosen/{jenis}/impor')->whereIn('jenis', $jenis)
    ->name('riwayat-impor.')->controller(ImporRiwayatController::class)->group(function () {
        Route::get('/', 'create')->name('create');
        Route::get('/templat', 'templat')->name('templat');
        Route::post('/periksa', 'periksa')->name('periksa');
        Route::post('/simpan', 'simpan')->name('simpan');
    });

// LPPM keeps the partner register; partners are switched off, never deleted.
Route::middleware(['auth', 'verified', 'role:'.Role::ADMIN_LPPM.','.Role::OPERATOR])->group(function () {
    Route::resource('mitra', MitraController::class)->except(['show', 'destroy'])->parameters(['mitra' => 'mitra']);
    Route::post('/mitra/{mitra}/verifikasi', [MitraController::class, 'verifikasi'])->name('mitra.verifikasi');
});

// The summary: everyone's for LPPM, operators and the heads; a unit head's own unit.
Route::middleware(['auth', 'verified', 'can:lihat-rekap-riwayat'])->get('/rekap-riwayat/{jenis}', [RekapRiwayatController::class, 'index'])
    ->whereIn('jenis', $jenis)->name('rekap-riwayat.index');
Route::middleware(['auth', 'verified', 'can:lihat-rekap-riwayat'])->get('/rekap-riwayat/{jenis}/ekspor', [RekapRiwayatController::class, 'ekspor'])
    ->whereIn('jenis', $jenis)->name('rekap-riwayat.ekspor');
