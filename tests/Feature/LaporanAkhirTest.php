<?php

use App\Enums\StatusProposal;
use App\Models\Laporan;
use App\Models\Luaran;
use App\Models\PencairanDana;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Notifications\Pemberitahuan;
use App\Services\AlurPelaksanaan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * A proposal under monev whose desk review is sent with the given result.
 */
function proposalSudahDimonev(string $hasil = 'sesuai'): Proposal
{
    $proposal = proposalDimonev();
    $penilaian = review()->tugaskanMonev($proposal, pengguna(Role::REVIEWER), pengguna(Role::ADMIN_LPPM));
    [$a, $b] = review()->kriteria($proposal, Penilaian::MONEV)->pluck('id')->all();
    review()->simpan($penilaian->fresh(), [$a => 4, $b => 4], ['rekomendasi' => $hasil, 'komentar_untuk_pengusul' => 'Catatan monev.'], true);

    return $proposal->fresh();
}

/**
 * @return array<string, mixed>
 */
function isianLaporanAkhir(): array
{
    return [
        'ringkasan' => 'Sistem irigasi terpasang dan diuji.',
        'persentase_capaian' => 100,
        'laporan' => UploadedFile::fake()->create('akhir.pdf', 50, 'application/pdf'),
        'keuangan' => UploadedFile::fake()->create('keuangan-akhir.pdf', 50, 'application/pdf'),
    ];
}

/**
 * The lead reports the required output as submitted and the operator accepts it.
 */
function luaranTervalidasi(Proposal $proposal): void
{
    $luaran = $proposal->luaran()->where('kategori', 'wajib')->sole();
    test()->actingAs($proposal->ketua->user)->put(route('proposal.pelaksanaan.luaran.update', [$proposal, $luaran]),
        ['status_capaian' => 'submitted', 'url_bukti' => 'https://jurnal.example.ac.id/artikel/12']);
    test()->actingAs(pengguna(Role::OPERATOR))->post(route('verifikasi.luaran', $luaran), ['aksi' => 'valid']);
}

beforeEach(fn () => Storage::fake());

test('the final report waits for the monev result', function () {
    $proposal = proposalDimonev();

    $this->actingAs($proposal->ketua->user)->post(route('proposal.pelaksanaan.laporan-akhir.store', $proposal), isianLaporanAkhir())
        ->assertSessionHasErrors('pelaksanaan');
    expect($proposal->laporan()->where('jenis', Laporan::AKHIR)->count())->toBe(0);
});

test('the operator sends a final report back and the lead sends a new one', function () {
    Notification::fake();
    $operator = pengguna(Role::OPERATOR);
    $proposal = proposalSudahDimonev();
    $ketua = $proposal->ketua->user;

    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.laporan-akhir.store', $proposal), isianLaporanAkhir())->assertSessionHasNoErrors();
    Notification::assertSentTo($operator, Pemberitahuan::class, fn ($n) => $n->judul === 'Laporan akhir masuk');
    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.laporan-akhir.store', $proposal), isianLaporanAkhir())->assertSessionHasErrors('pelaksanaan');

    $pertama = Laporan::where('jenis', Laporan::AKHIR)->sole();
    $this->actingAs($operator)->get(route('verifikasi.index'))->assertOk()->assertSee($proposal->judul);
    $this->actingAs($operator)->post(route('verifikasi.laporan', $pertama), ['aksi' => 'kembalikan'])->assertSessionHasErrors('pelaksanaan');
    $this->actingAs($operator)->post(route('verifikasi.laporan', $pertama), ['aksi' => 'kembalikan', 'catatan' => 'Lampirkan kuitansi.'])->assertSessionHasNoErrors();
    Notification::assertSentTo($ketua, Pemberitahuan::class, fn ($n) => $n->judul === 'Laporan akhir dikembalikan');
    $this->actingAs($ketua)->get(route('proposal.pelaksanaan.show', $proposal))->assertSee('Lampirkan kuitansi.')->assertSee('Kirim laporan akhir');

    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.laporan-akhir.store', $proposal), isianLaporanAkhir())->assertSessionHasNoErrors();
    $kedua = app(AlurPelaksanaan::class)->laporanAkhir($proposal);
    expect($kedua->id)->not->toBe($pertama->id)
        ->and($kedua->persentase_anggaran_terpakai)->toBe(100);

    $this->actingAs($operator)->post(route('verifikasi.laporan', $kedua), ['aksi' => 'terima'])->assertSessionHasNoErrors();
    expect($kedua->fresh()->status)->toBe(Laporan::DIVERIFIKASI)
        ->and($kedua->fresh()->diverifikasi_oleh)->toBe($operator->id);
});

test('a reported output goes back to waiting and a rejection needs a reason', function () {
    Notification::fake();
    $proposal = proposalBerjalan();
    $ketua = $proposal->ketua->user;
    $operator = pengguna(Role::OPERATOR);
    $luaran = $proposal->luaran()->sole();

    $this->actingAs($ketua)->put(route('proposal.pelaksanaan.luaran.update', [$proposal, $luaran]), ['status_capaian' => 'submitted'])
        ->assertSessionHasErrors('pelaksanaan');
    $this->actingAs(anggotaDosen($proposal))->put(route('proposal.pelaksanaan.luaran.update', [$proposal, $luaran]), ['status_capaian' => 'submitted', 'url_bukti' => 'https://contoh.id/a'])
        ->assertSessionHasErrors('pelaksanaan');
    $this->actingAs($ketua)->put(route('proposal.pelaksanaan.luaran.update', [$proposal, $luaran]), ['status_capaian' => 'submitted', 'url_bukti' => 'https://contoh.id/a'])
        ->assertSessionHasNoErrors();

    $this->actingAs($operator)->post(route('verifikasi.luaran', $luaran), ['aksi' => 'tolak'])->assertSessionHasErrors('pelaksanaan');
    $this->actingAs($operator)->post(route('verifikasi.luaran', $luaran), ['aksi' => 'tolak', 'catatan' => 'Tautan tidak bisa dibuka.'])->assertSessionHasNoErrors();
    expect($luaran->fresh()->status_validasi)->toBe(Luaran::DITOLAK);
    Notification::assertSentTo($ketua, Pemberitahuan::class, fn ($n) => $n->judul === 'Capaian luaran ditolak');

    $this->actingAs($ketua)->put(route('proposal.pelaksanaan.luaran.update', [$proposal, $luaran]), ['status_capaian' => 'diterima', 'url_bukti' => 'https://contoh.id/b']);
    expect($luaran->fresh()->status_validasi)->toBe(Luaran::MENUNGGU)
        ->and($luaran->fresh()->catatan_validasi)->toBeNull();
});

test('finance pays tranche two once the report and outputs are in, and the grant is done', function () {
    Notification::fake();
    $proposal = proposalSudahDimonev();
    $keuangan = pengguna(Role::KEUANGAN);
    $bukti = fn () => ['tgl_cair' => today()->toDateString(), 'pajak_lunas' => '1', 'bukti' => UploadedFile::fake()->create('transfer2.pdf', 10, 'application/pdf')];

    $this->actingAs($keuangan)->get(route('pencairan.index'))->assertSee('Laporan akhir belum diverifikasi.')->assertSee('Luaran wajib belum tervalidasi');
    $this->actingAs($keuangan)->post(route('pencairan.termin-2.store', $proposal), $bukti())->assertSessionHasErrors('pencairan');

    $this->actingAs($proposal->ketua->user)->post(route('proposal.pelaksanaan.laporan-akhir.store', $proposal), isianLaporanAkhir());
    $this->actingAs(pengguna(Role::OPERATOR))->post(route('verifikasi.laporan', app(AlurPelaksanaan::class)->laporanAkhir($proposal)), ['aksi' => 'terima']);
    luaranTervalidasi($proposal);

    $this->actingAs($keuangan)->get(route('pencairan.index'))->assertSee('Catat termin 2');
    $this->actingAs(pengguna(Role::ADMIN_LPPM))->post(route('pencairan.termin-2.store', $proposal), $bukti())->assertForbidden();
    $this->actingAs($keuangan)->post(route('pencairan.termin-2.store', $proposal), $bukti())->assertSessionHasNoErrors();

    $termin2 = PencairanDana::where('termin_ke', 2)->sole();
    expect($termin2->jumlah)->toBe(900_000)
        ->and($termin2->persentase)->toBe(20)
        ->and($termin2->syarat)->toBe(PencairanDana::SYARAT_LAPORAN_AKHIR)
        ->and($proposal->fresh()->status)->toBe(StatusProposal::Selesai)
        ->and((int) $proposal->pencairan()->sum('jumlah'))->toBe(4_500_000);
    Notification::assertSentTo($proposal->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Termin 2 dicairkan');
});

test('a troubled monev holds tranche two back', function () {
    $proposal = proposalSudahDimonev('bermasalah');
    $this->actingAs($proposal->ketua->user)->post(route('proposal.pelaksanaan.laporan-akhir.store', $proposal), isianLaporanAkhir());
    $this->actingAs(pengguna(Role::OPERATOR))->post(route('verifikasi.laporan', app(AlurPelaksanaan::class)->laporanAkhir($proposal)), ['aksi' => 'terima']);
    luaranTervalidasi($proposal);

    $this->actingAs(pengguna(Role::KEUANGAN))->post(route('pencairan.termin-2.store', $proposal), [
        'tgl_cair' => today()->toDateString(), 'bukti' => UploadedFile::fake()->create('t.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('pencairan');
    expect($proposal->fresh()->status)->toBe(StatusProposal::Dimonev);
});

test('leads are reminded 14, 7 and 1 days before a report is due', function () {
    Notification::fake();
    $proposal = proposalBerjalan();
    $ketua = $proposal->ketua->user;
    $alur = app(AlurPelaksanaan::class);

    $proposal->periode->update(['tgl_laporan_kemajuan' => today()->addDays(7)]);
    expect($alur->ingatkanTenggat())->toBe(1);
    Notification::assertSentTo($ketua, Pemberitahuan::class, fn ($n) => $n->judul === 'Pengingat laporan kemajuan');

    $proposal->periode->update(['tgl_laporan_kemajuan' => today()->addDays(5)]);
    expect($alur->ingatkanTenggat())->toBe(0);

    $this->artisan('laporan:ingatkan')->assertSuccessful();
});

test('a lead late with a report cannot send a new proposal', function () {
    $proposal = proposalBerjalan();
    $proposal->periode->update(['tgl_laporan_kemajuan' => today()->subDay()]);
    $baru = Proposal::factory()->lengkap()->create(['ketua_id' => $proposal->ketua_id]);

    expect(kekurangan($baru))->toContain("Kirim dulu laporan kemajuan \"{$proposal->judul}\" yang sudah lewat batas.");
});

test('a next-year proposal needs a monev result that is not troubled', function () {
    $induk = proposalSudahDimonev('bermasalah');
    $lanjutan = Proposal::factory()->lengkap()->create(['ketua_id' => $induk->ketua_id, 'proposal_induk_id' => $induk->id, 'tahun_ke' => 2]);

    expect(kekurangan($lanjutan))->toContain('Hasil monev tahun sebelumnya Bermasalah, sehingga usulan tahun berikutnya tidak bisa dikirim.');
});
