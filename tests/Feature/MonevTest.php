<?php

use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Berkas;
use App\Models\Dosen;
use App\Models\Laporan;
use App\Models\Logbook;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Services\AlurPencairan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * A funded proposal whose first tranche is paid, with a monev rubric of two
 * lines (weights 50 and 50, scale 1 to 5).
 */
function proposalBerjalan(): Proposal
{
    $proposal = proposalDisetujui();
    $admin = pengguna(Role::ADMIN_LPPM);
    $pencairan = app(AlurPencairan::class);
    $pencairan->catatKontrak($proposal, ['no_kontrak' => '045/KONTRAK/2026', 'tgl_kontrak' => today()->subMonth()->toDateString(),
        'tgl_mulai' => today()->subMonth()->toDateString(), 'tgl_selesai' => today()->addMonths(6)->toDateString()], $admin);
    $pencairan->cairkanTermin1($proposal->fresh(), today()->subMonth(), true, UploadedFile::fake()->create('transfer.pdf', 10, 'application/pdf'), pengguna(Role::KEUANGAN));

    $proposal->periode->kriteria()->create(['tahap' => Penilaian::MONEV, 'nama' => 'Kesesuaian capaian', 'bobot' => 50, 'skala_maks' => 5, 'urutan' => 1]);
    $proposal->periode->kriteria()->create(['tahap' => Penilaian::MONEV, 'nama' => 'Penggunaan dana', 'bobot' => 50, 'skala_maks' => 5, 'urutan' => 2]);

    return $proposal->fresh();
}

function anggotaDosen(Proposal $proposal, bool $konfirmasi = true): User
{
    $dosen = Dosen::factory()->create();
    $proposal->anggota()->create(['tipe' => TipeAnggota::Dosen, 'dosen_id' => $dosen->id, 'nama' => $dosen->namaLengkap(), 'peran' => 'Anggota'])
        ->forceFill(['dikonfirmasi_at' => $konfirmasi ? now() : null])->save();

    return $dosen->user;
}

/**
 * @return array<string, mixed>
 */
function isianLaporanKemajuan(): array
{
    return [
        'ringkasan' => 'Pengambilan data selesai, analisis berjalan.',
        'persentase_capaian' => 60,
        'persentase_anggaran_terpakai' => 70,
        'laporan' => UploadedFile::fake()->create('kemajuan.pdf', 50, 'application/pdf'),
        'keuangan' => UploadedFile::fake()->create('keuangan.pdf', 50, 'application/pdf'),
    ];
}

function proposalDimonev(): Proposal
{
    $proposal = proposalBerjalan();
    $ketua = $proposal->ketua->user;
    test()->actingAs($ketua)->post(route('proposal.pelaksanaan.logbook.store', $proposal), ['tanggal' => today()->toDateString(), 'kegiatan' => 'Survei lapangan.']);
    test()->actingAs($ketua)->post(route('proposal.pelaksanaan.laporan-kemajuan.store', $proposal), isianLaporanKemajuan());

    return $proposal->fresh();
}

beforeEach(fn () => Storage::fake());

test('the lead and confirmed lecturers keep the logbook', function () {
    $proposal = proposalBerjalan();
    $anggota = anggotaDosen($proposal);
    $belum = anggotaDosen($proposal, konfirmasi: false);

    $this->actingAs($proposal->ketua->user)->get(route('proposal.show', $proposal))->assertSee('Buka halaman pelaksanaan');
    $this->actingAs($anggota)->post(route('proposal.pelaksanaan.logbook.store', $proposal), [
        'tanggal' => today()->toDateString(), 'kegiatan' => 'Wawancara responden.', 'persentase_progres' => 30,
        'bukti' => UploadedFile::fake()->image('foto.jpg'),
    ])->assertSessionHasNoErrors();
    $this->actingAs($belum)->post(route('proposal.pelaksanaan.logbook.store', $proposal), ['tanggal' => today()->toDateString(), 'kegiatan' => 'Coba.'])
        ->assertSessionHasErrors('pelaksanaan');

    $entri = Logbook::sole();
    expect($entri->pencatat->id)->toBe($anggota->dosen->id)
        ->and($entri->persentase_progres)->toBe(30)
        ->and($entri->berkas()->sole()->kategori)->toBe(Berkas::BUKTI_LOGBOOK);
    $this->actingAs($proposal->ketua->user)->get(route('proposal.pelaksanaan.show', $proposal))->assertOk()->assertSee('Wawancara responden.');
    $this->actingAs($proposal->ketua->user)->get(route('proposal.pelaksanaan.berkas', [$proposal, $entri->berkas->first()]))->assertDownload('foto.jpg');
});

test('a logbook entry cannot be dated in the future or before the contract starts', function () {
    $proposal = proposalBerjalan();
    $ketua = $proposal->ketua->user;

    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.logbook.store', $proposal), ['tanggal' => today()->addDay()->toDateString(), 'kegiatan' => 'Besok.'])
        ->assertSessionHasErrors('pelaksanaan');
    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.logbook.store', $proposal), ['tanggal' => today()->subYear()->toDateString(), 'kegiatan' => 'Dulu.'])
        ->assertSessionHasErrors('pelaksanaan');
    expect(Logbook::count())->toBe(0);
});

test('only the writer removes a logbook entry', function () {
    $proposal = proposalBerjalan();
    $anggota = anggotaDosen($proposal);
    $this->actingAs($anggota)->post(route('proposal.pelaksanaan.logbook.store', $proposal), ['tanggal' => today()->toDateString(), 'kegiatan' => 'Rapat tim.']);
    $entri = Logbook::sole();

    $this->actingAs($proposal->ketua->user)->delete(route('proposal.pelaksanaan.logbook.destroy', [$proposal, $entri]))->assertSessionHasErrors('pelaksanaan');
    $this->actingAs($anggota)->delete(route('proposal.pelaksanaan.logbook.destroy', [$proposal, $entri]))->assertSessionHasNoErrors();

    expect(Logbook::count())->toBe(0);
});

test('the lead sends the progress report and the proposal goes to monev', function () {
    Notification::fake();
    $admin = pengguna(Role::ADMIN_LPPM);
    $proposal = proposalBerjalan();
    $ketua = $proposal->ketua->user;

    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.laporan-kemajuan.store', $proposal), isianLaporanKemajuan())
        ->assertSessionHasErrors('pelaksanaan');
    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.logbook.store', $proposal), ['tanggal' => today()->toDateString(), 'kegiatan' => 'Survei.']);
    $anggota = anggotaDosen($proposal);
    $this->actingAs($anggota)->post(route('proposal.pelaksanaan.laporan-kemajuan.store', $proposal), isianLaporanKemajuan())
        ->assertSessionHasErrors('pelaksanaan');
    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.laporan-kemajuan.store', $proposal), isianLaporanKemajuan())
        ->assertSessionHasNoErrors();

    $laporan = Laporan::sole();
    expect($proposal->fresh()->status)->toBe(StatusProposal::Dimonev)
        ->and($laporan->jenis)->toBe(Laporan::KEMAJUAN)
        ->and($laporan->status)->toBe(Laporan::DIAJUKAN)
        ->and($laporan->persentase_capaian)->toBe(60)
        ->and($laporan->berkas->pluck('kategori')->sort()->values()->all())->toBe([Berkas::LAPORAN_KEMAJUAN, Berkas::LAPORAN_KEUANGAN]);
    Notification::assertSentTo($admin, Pemberitahuan::class, fn ($n) => $n->judul === 'Laporan kemajuan masuk');

    $this->actingAs($ketua)->post(route('proposal.pelaksanaan.laporan-kemajuan.store', $proposal), isianLaporanKemajuan())
        ->assertSessionHasErrors('pelaksanaan');
    expect(Laporan::count())->toBe(1);
});

test('LPPM assigns one monev reviewer who reads the report blind and sends a result', function () {
    Notification::fake();
    $proposal = proposalDimonev();
    $admin = pengguna(Role::ADMIN_LPPM);
    $reviewer = pengguna(Role::REVIEWER);

    $this->actingAs($admin)->get(route('admin.monev.index'))->assertOk()->assertSee($proposal->judul);
    $this->actingAs($admin)->post(route('admin.monev.store', $proposal), ['reviewer_id' => $reviewer->id])->assertSessionHasNoErrors();

    $penilaian = $proposal->penilaian()->where('tahap', Penilaian::MONEV)->sole();
    expect($penilaian->laporan_id)->toBe(Laporan::sole()->id);
    Notification::assertSentTo($reviewer, Pemberitahuan::class, fn ($n) => $n->judul === 'Tugas monev baru');

    $this->actingAs($reviewer)->get(route('review.index'))->assertSee('Monev');
    $this->actingAs($reviewer)->get(route('review.show', $penilaian))->assertOk()
        ->assertSee('Pengambilan data selesai')->assertSee('Survei lapangan.')->assertSee('Sesuai rencana')
        ->assertDontSee($proposal->ketua->user->name);
    $berkas = Laporan::sole()->berkas->firstWhere('kategori', Berkas::LAPORAN_KEMAJUAN);
    $this->actingAs($reviewer)->get(route('review.laporan', [$penilaian, $berkas]))->assertDownload('laporan_kemajuan-'.$proposal->id.'.pdf');

    [$a, $b] = review()->kriteria($proposal, Penilaian::MONEV)->pluck('id')->all();
    $this->actingAs($reviewer)->put(route('review.update', $penilaian), [
        'aksi' => 'kirim', 'skor' => [$a => 5, $b => 3], 'rekomendasi' => 'didanai', 'komentar_untuk_pengusul' => 'Lanjutkan.',
    ])->assertSessionHasErrors('penilaian');
    $this->actingAs($reviewer)->put(route('review.update', $penilaian), [
        'aksi' => 'kirim', 'skor' => [$a => 5, $b => 3], 'rekomendasi' => 'tindak_lanjut', 'komentar_untuk_pengusul' => 'Percepat analisis.',
    ])->assertSessionHasNoErrors();

    $penilaian->refresh();
    expect($penilaian->terkirim())->toBeTrue()
        ->and((float) $penilaian->nilai_total)->toBe(80.0)
        ->and($penilaian->namaRekomendasi())->toBe('Perlu tindak lanjut');
    Notification::assertSentTo($admin, Pemberitahuan::class, fn ($n) => $n->judul === 'Hasil monev dikirim');
    Notification::assertSentTo($proposal->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Hasil monev');
    $this->actingAs($proposal->ketua->user)->get(route('proposal.pelaksanaan.show', $proposal))
        ->assertSee('Perlu tindak lanjut')->assertSee('Percepat analisis.')->assertDontSee($reviewer->name);

    $this->actingAs($admin)->post(route('admin.monev.buka', [$proposal, $penilaian]), ['alasan' => 'Tambahkan nilai.'])->assertSessionHasNoErrors();
    expect($penilaian->fresh()->terkirim())->toBeFalse();
});

test('a monev reviewer is not taken from the team and needs a monev rubric', function () {
    $proposal = proposalDimonev();
    $admin = pengguna(Role::ADMIN_LPPM);
    $anggota = anggotaDosen($proposal);
    $anggota->assignRole(Role::REVIEWER);

    $this->actingAs($admin)->post(route('admin.monev.store', $proposal), ['reviewer_id' => $anggota->id])->assertSessionHasErrors('penilaian');

    $proposal->periode->kriteria()->where('tahap', Penilaian::MONEV)->delete();
    $this->actingAs($admin)->post(route('admin.monev.store', $proposal), ['reviewer_id' => pengguna(Role::REVIEWER)->id])->assertSessionHasErrors('penilaian');
    expect($proposal->penilaian()->where('tahap', Penilaian::MONEV)->count())->toBe(0);
});

test('a reviewer cannot download files of a report that is not theirs', function () {
    $proposal = proposalDimonev();
    $reviewer = pengguna(Role::REVIEWER);
    $penilaian = review()->tugaskanMonev($proposal, $reviewer, pengguna(Role::ADMIN_LPPM));
    $lain = $proposal->berkasSubstansi;

    $this->actingAs($reviewer)->get(route('review.laporan', [$penilaian, $lain]))->assertNotFound();
    $this->actingAs(pengguna(Role::REVIEWER))->get(route('review.laporan', [$penilaian, Laporan::sole()->berkas->first()]))->assertForbidden();
});

test('outsiders cannot open the logbook page', function () {
    $proposal = proposalBerjalan();

    $this->actingAs(pengguna(Role::DOSEN))->get(route('proposal.pelaksanaan.show', $proposal))->assertForbidden();
    $this->actingAs(pengguna(Role::OPERATOR))->get(route('proposal.pelaksanaan.show', $proposal))->assertOk();
});

test('the operator sees quiet logbooks, late progress reports and monev without a result', function () {
    $sepi = proposalBerjalan();
    $sepi->periode->update(['tgl_laporan_kemajuan' => today()->subDay()]);
    $dimonev = proposalDimonev();
    $operator = pengguna(Role::OPERATOR);

    $this->actingAs($operator)->get(route('pemantauan.index'))->assertOk()
        ->assertSeeInOrder(['Logbook tidak diisi 14 hari (1)', $sepi->judul, 'Belum pernah diisi'])
        ->assertSeeInOrder(['Laporan kemajuan terlambat (1)', $sepi->judul])
        ->assertSeeInOrder(['Monev belum ada hasil (1)', $dimonev->judul, 'Reviewer belum ditugaskan']);
    $this->actingAs(pengguna(Role::DOSEN))->get(route('pemantauan.index'))->assertForbidden();
});
