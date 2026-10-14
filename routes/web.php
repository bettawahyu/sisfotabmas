<?php

use App\Http\Controllers\Admin\KontrakController;
use App\Http\Controllers\Admin\KriteriaPenilaianController;
use App\Http\Controllers\Admin\MonevController;
use App\Http\Controllers\Admin\PenetapanController;
use App\Http\Controllers\Admin\PenugasanReviewerController;
use App\Http\Controllers\Admin\PeriodeHibahController;
use App\Http\Controllers\Admin\PimpinanUnitController;
use App\Http\Controllers\Admin\SeleksiController;
use App\Http\Controllers\Admin\SkemaHibahController;
use App\Http\Controllers\Admin\TemplateDokumenController as KelolaTemplateController;
use App\Http\Controllers\Kelola\PengumumanController as KelolaPengumumanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PelaksanaanController;
use App\Http\Controllers\PemantauanController;
use App\Http\Controllers\PencairanController;
use App\Http\Controllers\PengesahanController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\PimpinanController;
use App\Http\Controllers\ProfilDosenController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalBagianController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Sistem\PenggunaController;
use App\Http\Controllers\TemplateDokumenController;
use App\Http\Controllers\UndanganController;
use App\Http\Controllers\VerifikasiController;
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

    Route::get('/pelaksanaan', [PelaksanaanController::class, 'index'])->name('pelaksanaan.index');

    Route::get('/undangan/{token}', [UndanganController::class, 'show'])->name('undangan.show');
    Route::post('/undangan/{token}', [UndanganController::class, 'jawab'])->name('undangan.jawab');
});

// The proposal page is shared by the team, LPPM and faculty heads; ProposalPolicy decides.
Route::middleware('auth')->group(function () {
    Route::get('/proposal/{proposal}', [ProposalController::class, 'show'])->name('proposal.show');
    Route::get('/proposal/{proposal}/substansi', [ProposalBagianController::class, 'unduhSubstansi'])->name('proposal.substansi.show');

    // The team writes the logbook and reports; AlurPelaksanaan checks who.
    Route::controller(PelaksanaanController::class)->prefix('/pelaksanaan/{proposal}')->name('pelaksanaan.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/logbook', 'storeLogbook')->name('logbook.store');
        Route::delete('/logbook/{logbook}', 'destroyLogbook')->name('logbook.destroy');
        Route::post('/laporan-kemajuan', 'storeLaporanKemajuan')->name('laporan-kemajuan.store');
        Route::post('/laporan-akhir', 'storeLaporanAkhir')->name('laporan-akhir.store');
        Route::put('/luaran/{luaran}', 'updateLuaran')->name('luaran.update');
        Route::get('/berkas/{berkas}', 'berkas')->name('berkas');
    });
    Route::redirect('/proposal/{proposal}/pelaksanaan', '/pelaksanaan/{proposal}', 301);
});

Route::middleware(['auth', 'role:'.Role::OPERATOR.','.Role::ADMIN_LPPM])->group(function () {
    Route::get('/pemantauan', [PemantauanController::class, 'index'])->name('pemantauan.index');
    Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
    Route::post('/verifikasi/laporan/{laporan}', [VerifikasiController::class, 'laporan'])->name('verifikasi.laporan');
    Route::post('/verifikasi/luaran/{luaran}', [VerifikasiController::class, 'luaran'])->name('verifikasi.luaran');
});

Route::middleware(['auth', 'role:'.Role::REVIEWER])->prefix('review')->name('review.')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->name('index');
    Route::get('/{penilaian}', [ReviewController::class, 'show'])->name('show');
    Route::put('/{penilaian}', [ReviewController::class, 'update'])->name('update');
    Route::get('/{penilaian}/substansi', [ReviewController::class, 'substansi'])->name('substansi');
    Route::get('/{penilaian}/laporan/{berkas}', [ReviewController::class, 'laporan'])->name('laporan');
});

// Finance records tranches; LPPM admins only look.
Route::middleware(['auth', 'role:'.Role::KEUANGAN.','.Role::ADMIN_LPPM])->prefix('pencairan')->name('pencairan.')->group(function () {
    Route::get('/', [PencairanController::class, 'index'])->name('index');
    Route::post('/{proposal}/termin-1', [PencairanController::class, 'store'])->name('store');
    Route::post('/{proposal}/termin-2', [PencairanController::class, 'storeTermin2'])->name('termin-2.store');
    Route::get('/bukti/{pencairan}', [PencairanController::class, 'bukti'])->name('bukti');
});

Route::middleware(['auth', 'role:'.Role::PIMPINAN_UNIT])->prefix('pengesahan')->name('pengesahan.')->group(function () {
    Route::get('/', [PengesahanController::class, 'index'])->name('index');
    Route::post('/{proposal}/sahkan', [PengesahanController::class, 'sahkan'])->name('sahkan');
    Route::post('/{proposal}/tolak', [PengesahanController::class, 'tolak'])->name('tolak');
});

// The LPPM or institution head only reads the summary.
Route::middleware(['auth', 'role:'.Role::PIMPINAN])->prefix('pimpinan')->name('pimpinan.')->group(function () {
    Route::get('/', [PimpinanController::class, 'index'])->name('index');
});

Route::middleware(['auth', 'role:'.Role::ADMIN_SISTEM])->prefix('sistem')->name('sistem.')->group(function () {
    Route::redirect('/', '/sistem/pengguna')->name('dashboard');
    Route::resource('pengguna', PenggunaController::class)->except(['show', 'destroy'])
        ->parameters(['pengguna' => 'pengguna']);
});

Route::middleware(['auth', 'role:'.Role::ADMIN_LPPM])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');

    Route::resource('skema', SkemaHibahController::class)->except(['show', 'destroy']);
    Route::resource('periode', PeriodeHibahController::class)->except(['show', 'destroy']);
    Route::resource('template', KelolaTemplateController::class)->except(['show', 'destroy']);

    Route::post('/periode/{periode}/kriteria', [KriteriaPenilaianController::class, 'store'])->name('periode.kriteria.store');
    Route::delete('/periode/{periode}/kriteria/{kriteria}', [KriteriaPenilaianController::class, 'destroy'])->name('periode.kriteria.destroy');

    Route::get('/seleksi', [SeleksiController::class, 'index'])->name('seleksi.index');
    Route::post('/seleksi/{proposal}/lolos', [SeleksiController::class, 'lolos'])->name('seleksi.lolos');
    Route::post('/seleksi/{proposal}/kembalikan', [SeleksiController::class, 'kembalikan'])->name('seleksi.kembalikan');
    Route::post('/seleksi/{proposal}/tolak', [SeleksiController::class, 'tolak'])->name('seleksi.tolak');

    Route::controller(PenetapanController::class)->prefix('penetapan')->name('penetapan.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{periode}', 'show')->name('show');
        Route::post('/{periode}', 'store')->name('store');
        Route::post('/{periode}/sk', 'sk')->name('sk');
    });

    Route::get('/pimpinan-unit', [PimpinanUnitController::class, 'index'])->name('pimpinan-unit.index');
    Route::put('/pimpinan-unit/{user}', [PimpinanUnitController::class, 'update'])->name('pimpinan-unit.update');

    Route::get('/kontrak', [KontrakController::class, 'index'])->name('kontrak.index');
    Route::post('/kontrak/{proposal}', [KontrakController::class, 'store'])->name('kontrak.store');

    Route::controller(MonevController::class)->prefix('monev')->name('monev.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{proposal}', 'store')->name('store');
        Route::delete('/{proposal}/penilaian/{penilaian}', 'destroy')->name('destroy');
        Route::post('/{proposal}/penilaian/{penilaian}/buka', 'bukaKembali')->name('buka');
    });

    Route::controller(PenugasanReviewerController::class)->prefix('review')->name('review.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{proposal}', 'show')->name('show');
        Route::post('/{proposal}', 'store')->name('store');
        Route::delete('/{proposal}/penilaian/{penilaian}', 'destroy')->name('destroy');
        Route::post('/{proposal}/penilaian/{penilaian}/buka', 'bukaKembali')->name('buka');
    });
});

require __DIR__.'/karya.php';
require __DIR__.'/auth.php';
