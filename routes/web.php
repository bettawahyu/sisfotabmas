<?php

use App\Http\Controllers\Admin\PeriodeHibahController;
use App\Http\Controllers\Admin\SeleksiController;
use App\Http\Controllers\Admin\SkemaHibahController;
use App\Http\Controllers\Admin\TemplateDokumenController as KelolaTemplateController;
use App\Http\Controllers\Kelola\PengumumanController as KelolaPengumumanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PengesahanController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\ProfilDosenController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalBagianController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\TemplateDokumenController;
use App\Http\Controllers\UndanganController;
use App\Models\Pengumuman;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function (Request $request) {
    return view('dashboard', [
        'pengumuman' => Pengumuman::tampil()->untuk($request->user())->limit(5)->get(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman.index');
    Route::get('/pengumuman/{pengumuman}', [PengumumanController::class, 'show'])->name('pengumuman.show');
    Route::get('/pengumuman/{pengumuman}/lampiran', [PengumumanController::class, 'lampiran'])->name('pengumuman.lampiran');

    Route::get('/templat/{template}/unduh', [TemplateDokumenController::class, 'unduh'])->name('templat.unduh');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/{id}', [NotifikasiController::class, 'buka'])->name('notifikasi.buka');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
});

// Announcements are kept by LPPM admins and operators.
Route::middleware(['auth', 'can:kelola-pengumuman'])->prefix('kelola')->name('kelola.')->group(function () {
    Route::resource('pengumuman', KelolaPengumumanController::class)->except('show')
        ->parameters(['pengumuman' => 'pengumuman']);
});

Route::middleware(['auth', 'role:'.Role::DOSEN])->group(function () {
    Route::get('/profil-dosen', [ProfilDosenController::class, 'edit'])->name('profil-dosen.edit');
    Route::put('/profil-dosen', [ProfilDosenController::class, 'update'])->name('profil-dosen.update');

    Route::get('/proposal', [ProposalController::class, 'index'])->name('proposal.index');
    Route::get('/hibah/{periode}/proposal/buat', [ProposalController::class, 'create'])->name('proposal.create');
    Route::post('/hibah/{periode}/proposal', [ProposalController::class, 'store'])->name('proposal.store');
    Route::put('/proposal/{proposal}', [ProposalController::class, 'update'])->name('proposal.update');
    Route::delete('/proposal/{proposal}', [ProposalController::class, 'destroy'])->name('proposal.destroy');
    Route::post('/proposal/{proposal}/kirim', [ProposalController::class, 'kirim'])->name('proposal.kirim');

    Route::controller(ProposalBagianController::class)->prefix('/proposal/{proposal}')->name('proposal.')->group(function () {
        Route::post('/anggota', 'tambahAnggota')->name('anggota.store');
        Route::delete('/anggota/{anggota}', 'hapusAnggota')->name('anggota.destroy');
        Route::post('/mitra', 'tambahMitra')->name('mitra.store');
        Route::delete('/mitra/{mitra}', 'hapusMitra')->name('mitra.destroy');
        Route::post('/rab', 'tambahRab')->name('rab.store');
        Route::delete('/rab/{rab}', 'hapusRab')->name('rab.destroy');
        Route::post('/luaran', 'tambahLuaran')->name('luaran.store');
        Route::delete('/luaran/{luaran}', 'hapusLuaran')->name('luaran.destroy');
        Route::post('/substansi', 'unggahSubstansi')->name('substansi.store');
    });

    Route::get('/undangan/{token}', [UndanganController::class, 'show'])->name('undangan.show');
    Route::post('/undangan/{token}', [UndanganController::class, 'jawab'])->name('undangan.jawab');
});

// The proposal page is shared by the team, LPPM and faculty heads; ProposalPolicy decides.
Route::middleware('auth')->group(function () {
    Route::get('/proposal/{proposal}', [ProposalController::class, 'show'])->name('proposal.show');
    Route::get('/proposal/{proposal}/substansi', [ProposalBagianController::class, 'unduhSubstansi'])->name('proposal.substansi.show');
});

Route::middleware(['auth', 'role:'.Role::PIMPINAN_UNIT])->prefix('pengesahan')->name('pengesahan.')->group(function () {
    Route::get('/', [PengesahanController::class, 'index'])->name('index');
    Route::post('/{proposal}/sahkan', [PengesahanController::class, 'sahkan'])->name('sahkan');
    Route::post('/{proposal}/tolak', [PengesahanController::class, 'tolak'])->name('tolak');
});

Route::middleware(['auth', 'role:'.Role::ADMIN_LPPM])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');

    Route::resource('skema', SkemaHibahController::class)->except(['show', 'destroy']);
    Route::resource('periode', PeriodeHibahController::class)->except(['show', 'destroy']);
    Route::resource('template', KelolaTemplateController::class)->except(['show', 'destroy']);

    Route::get('/seleksi', [SeleksiController::class, 'index'])->name('seleksi.index');
    Route::post('/seleksi/{proposal}/lolos', [SeleksiController::class, 'lolos'])->name('seleksi.lolos');
    Route::post('/seleksi/{proposal}/kembalikan', [SeleksiController::class, 'kembalikan'])->name('seleksi.kembalikan');
    Route::post('/seleksi/{proposal}/tolak', [SeleksiController::class, 'tolak'])->name('seleksi.tolak');
});

require __DIR__.'/auth.php';
