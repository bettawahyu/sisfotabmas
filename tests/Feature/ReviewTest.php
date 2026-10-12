<?php

use App\Enums\TipeAnggota;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Services\AlurReview;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function review(): AlurReview
{
    return app(AlurReview::class);
}

/**
 * A proposal that passed the administrative screening, with a two-line rubric
 * (weights 60 and 40, scale 1 to 5).
 */
function proposalDitinjau(): Proposal
{
    $proposal = Proposal::factory()->lengkap()->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);
    alur()->sahkan($proposal->fresh(), pengguna(Role::PIMPINAN_UNIT));
    alur()->lolosAdministrasi($proposal->fresh(), pengguna(Role::ADMIN_LPPM));

    $proposal->periode->kriteria()->create(['tahap' => Penilaian::SELEKSI, 'nama' => 'Metode', 'bobot' => 60, 'skala_maks' => 5, 'urutan' => 1]);
    $proposal->periode->kriteria()->create(['tahap' => Penilaian::SELEKSI, 'nama' => 'Luaran', 'bobot' => 40, 'skala_maks' => 5, 'urutan' => 2]);

    return $proposal->fresh();
}

/**
 * @return array<int, int>
 */
function skorPenuh(Proposal $proposal, int $metode, int $luaran): array
{
    [$a, $b] = review()->kriteria($proposal)->pluck('id')->all();

    return [$a => $metode, $b => $luaran];
}

$isian = ['rekomendasi' => 'didanai', 'komentar_untuk_pengusul' => 'Metode jelas.', 'catatan_internal' => 'Layak.'];

test('LPPM assigns two reviewers and each is notified', function () {
    Notification::fake();
    $proposal = proposalDitinjau();
    $admin = pengguna(Role::ADMIN_LPPM);
    [$r1, $r2] = [pengguna(Role::REVIEWER), pengguna(Role::REVIEWER)];

    $this->actingAs($admin)->post(route('admin.review.store', $proposal), ['urutan' => 1, 'reviewer_id' => $r1->id])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.review.store', $proposal), ['urutan' => 2, 'reviewer_id' => $r2->id])->assertRedirect();

    expect($proposal->penilaian()->where('tahap', Penilaian::SELEKSI)->pluck('reviewer_id', 'urutan_reviewer')->all())
        ->toBe([1 => $r1->id, 2 => $r2->id]);
    Notification::assertSentTo($r1, Pemberitahuan::class, fn ($n) => $n->judul === 'Tugas review proposal baru');

    $this->actingAs($admin)->get(route('admin.review.show', $proposal))->assertOk()->assertSee($r1->name)->assertSee($r2->name);
});

test('there is no third reviewer slot', function () {
    review()->tugaskan(proposalDitinjau(), pengguna(Role::REVIEWER), 3, pengguna(Role::ADMIN_LPPM));
})->throws(ValidationException::class, 'Proposal hanya dinilai oleh dua reviewer.');

test('a member of the team cannot review the proposal', function () {
    $proposal = proposalDitinjau();
    $anggota = Dosen::factory()->create();
    $anggota->user->assignRole(Role::REVIEWER);
    $proposal->anggota()->create(['tipe' => TipeAnggota::Dosen, 'dosen_id' => $anggota->id, 'nama' => $anggota->nama]);

    review()->tugaskan($proposal, $anggota->user, 1, pengguna(Role::ADMIN_LPPM));
})->throws(ValidationException::class, 'Reviewer tidak boleh ketua atau anggota proposal yang dinilainya.');

test('the same reviewer cannot take both slots', function () {
    $proposal = proposalDitinjau();
    $reviewer = pengguna(Role::REVIEWER);
    review()->tugaskan($proposal, $reviewer, 1, pengguna(Role::ADMIN_LPPM));

    review()->tugaskan($proposal, $reviewer, 2, pengguna(Role::ADMIN_LPPM));
})->throws(ValidationException::class, 'Reviewer itu sudah ditugaskan di slot lain proposal ini.');

test('a reviewer from the lead\'s programme is allowed with a warning', function () {
    $proposal = proposalDitinjau();
    $prodi = Fakultas::create(['kode' => 'FT', 'nama' => 'Teknik'])->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $proposal->ketua->update(['prodi_id' => $prodi->id]);
    $rekan = Dosen::factory()->create(['prodi_id' => $prodi->id]);
    $rekan->user->assignRole(Role::REVIEWER);

    $this->actingAs(pengguna(Role::ADMIN_LPPM))
        ->post(route('admin.review.store', $proposal->fresh()), ['urutan' => 1, 'reviewer_id' => $rekan->user_id])
        ->assertSessionHas('status', fn ($pesan) => str_contains($pesan, 'program studi yang sama'));

    expect($proposal->penilaian()->count())->toBe(1);
});

test('the reviewer sees the proposal without the team names', function () {
    $proposal = proposalDitinjau();
    $reviewer = pengguna(Role::REVIEWER);
    $penilaian = review()->tugaskan($proposal, $reviewer, 1, pengguna(Role::ADMIN_LPPM));

    $this->actingAs($reviewer)->get(route('review.index'))->assertOk()->assertSee($proposal->judul);
    $this->actingAs($reviewer)->get(route('review.show', $penilaian))
        ->assertOk()
        ->assertSee($proposal->judul)
        ->assertSee('Metode')
        ->assertDontSee($proposal->ketua->nama);

    // The ordinary proposal page, which names the team, stays closed to reviewers.
    $this->actingAs($reviewer)->get(route('proposal.show', $proposal))->assertForbidden();
});

test('another reviewer cannot open someone else\'s task', function () {
    $penilaian = review()->tugaskan(proposalDitinjau(), pengguna(Role::REVIEWER), 1, pengguna(Role::ADMIN_LPPM));

    $this->actingAs(pengguna(Role::REVIEWER))->get(route('review.show', $penilaian))->assertForbidden();
});

test('a draft can be partial, sending needs every score and the comment', function () use ($isian) {
    $proposal = proposalDitinjau();
    $reviewer = pengguna(Role::REVIEWER);
    $penilaian = review()->tugaskan($proposal, $reviewer, 1, pengguna(Role::ADMIN_LPPM));
    [$metode] = review()->kriteria($proposal)->pluck('id')->all();

    $this->actingAs($reviewer)->put(route('review.update', $penilaian), ['skor' => [$metode => 4], 'aksi' => 'simpan'])->assertSessionHasNoErrors();
    expect($penilaian->fresh()->status)->toBe(Penilaian::DITUGASKAN);

    $this->actingAs($reviewer)->put(route('review.update', $penilaian), ['skor' => [$metode => 4], 'aksi' => 'kirim'] + $isian)
        ->assertSessionHasErrors('penilaian');
    expect($penilaian->fresh()->terkirim())->toBeFalse();
});

test('sending computes the weighted score and locks the review', function () use ($isian) {
    Notification::fake();
    $admin = pengguna(Role::ADMIN_LPPM);
    $proposal = proposalDitinjau();
    $reviewer = pengguna(Role::REVIEWER);
    $penilaian = review()->tugaskan($proposal, $reviewer, 1, $admin);

    $this->actingAs($reviewer)
        ->put(route('review.update', $penilaian), ['skor' => skorPenuh($proposal, 4, 5), 'aksi' => 'kirim'] + $isian)
        ->assertRedirect(route('review.index'));

    // 4/5 x 60 + 5/5 x 40 = 88 out of 100.
    $penilaian->refresh();
    expect($penilaian->terkirim())->toBeTrue()
        ->and((float) $penilaian->nilai_total)->toBe(88.0)
        ->and($penilaian->dinilai_at)->not->toBeNull();
    Notification::assertSentTo($admin, Pemberitahuan::class, fn ($n) => $n->judul === 'Penilaian proposal dikirim');

    $this->actingAs($reviewer)->get(route('review.index', ['tab' => 'selesai']))->assertSee($proposal->judul);
    $this->actingAs($reviewer)->put(route('review.update', $penilaian), ['skor' => skorPenuh($proposal, 1, 1), 'aksi' => 'kirim'] + $isian)
        ->assertSessionHasErrors('penilaian');
    expect((float) $penilaian->fresh()->nilai_total)->toBe(88.0);
});

test('LPPM can reopen a sent review and the reviewer is told why', function () use ($isian) {
    Notification::fake();
    $proposal = proposalDitinjau();
    $reviewer = pengguna(Role::REVIEWER);
    $penilaian = review()->tugaskan($proposal, $reviewer, 1, pengguna(Role::ADMIN_LPPM));
    review()->simpan($penilaian, skorPenuh($proposal, 3, 3), $isian, kirim: true);

    $this->actingAs(pengguna(Role::ADMIN_LPPM))
        ->post(route('admin.review.buka', [$proposal, $penilaian]), ['alasan' => 'Skor metode tertukar.'])
        ->assertRedirect();

    expect($penilaian->fresh()->terkirim())->toBeFalse();
    Notification::assertSentTo($reviewer, Pemberitahuan::class, fn ($n) => str_contains($n->pesan, 'Skor metode tertukar.'));
});

test('a sent review cannot be reassigned until it is reopened', function () use ($isian) {
    $proposal = proposalDitinjau();
    $penilaian = review()->tugaskan($proposal, pengguna(Role::REVIEWER), 1, pengguna(Role::ADMIN_LPPM));
    review()->simpan($penilaian, skorPenuh($proposal, 3, 3), $isian, kirim: true);

    review()->tugaskan($proposal->fresh(), pengguna(Role::REVIEWER), 1, pengguna(Role::ADMIN_LPPM));
})->throws(ValidationException::class);

test('an unsent assignment can be handed to another reviewer', function () {
    $proposal = proposalDitinjau();
    review()->tugaskan($proposal, pengguna(Role::REVIEWER), 1, pengguna(Role::ADMIN_LPPM));
    $pengganti = pengguna(Role::REVIEWER);

    review()->tugaskan($proposal->fresh(), $pengganti, 1, pengguna(Role::ADMIN_LPPM));

    expect($proposal->penilaian()->sole()->reviewer_id)->toBe($pengganti->id);
});

test('reviewers are reminded three days before the selection deadline', function () {
    Notification::fake();
    $proposal = proposalDitinjau();
    $proposal->periode->update(['tgl_seleksi_selesai' => today()->addDays(3)]);
    $reviewer = pengguna(Role::REVIEWER);
    review()->tugaskan($proposal, $reviewer, 1, pengguna(Role::ADMIN_LPPM));

    $this->artisan('review:ingatkan')->assertSuccessful();

    Notification::assertSentTo($reviewer, Pemberitahuan::class, fn ($n) => $n->judul === 'Pengingat batas penilaian');
});

test('LPPM manages the rubric on the call page', function () {
    $proposal = proposalDitinjau();
    $admin = pengguna(Role::ADMIN_LPPM);

    $this->actingAs($admin)->post(route('admin.periode.kriteria.store', $proposal->periode), [
        'tahap' => Penilaian::SELEKSI, 'nama' => 'Kelayakan anggaran', 'bobot' => 10, 'skala_maks' => 7,
    ])->assertRedirect();

    $this->actingAs($admin)->get(route('admin.periode.edit', $proposal->periode))->assertOk()->assertSee('Kelayakan anggaran');
    expect(review()->kriteria($proposal)->last()->urutan)->toBe(3);
});

test('only reviewers reach the review pages', function () {
    $this->actingAs(pengguna(Role::DOSEN))->get(route('review.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('admin.review.index'))->assertForbidden();
});
