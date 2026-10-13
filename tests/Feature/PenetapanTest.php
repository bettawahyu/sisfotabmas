<?php

use App\Enums\StatusProposal;
use App\Models\Penilaian;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\Role;
use App\Notifications\Pemberitahuan;
use App\Services\AlurPenetapan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function penetapan(): AlurPenetapan
{
    return app(AlurPenetapan::class);
}

/**
 * A reviewed proposal in the given call whose two reviewers gave these scores
 * on the two-line rubric (weights 60/40, scale 1-5).
 */
function proposalDinilai(PeriodeHibah $periode, array $skor1, array $skor2, string $komentar = 'Komentar reviewer.'): Proposal
{
    $proposal = Proposal::factory()->lengkap()->for($periode, 'periode')->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);
    alur()->sahkan($proposal->fresh(), pengguna(Role::PIMPINAN_UNIT));
    alur()->lolosAdministrasi($proposal->fresh(), pengguna(Role::ADMIN_LPPM));

    if ($periode->kriteria()->doesntExist()) {
        $periode->kriteria()->create(['tahap' => Penilaian::SELEKSI, 'nama' => 'Metode', 'bobot' => 60, 'skala_maks' => 5, 'urutan' => 1]);
        $periode->kriteria()->create(['tahap' => Penilaian::SELEKSI, 'nama' => 'Luaran', 'bobot' => 40, 'skala_maks' => 5, 'urutan' => 2]);
    }

    foreach ([1 => $skor1, 2 => $skor2] as $urutan => $skor) {
        if ($skor === []) {
            continue;
        }
        $pen = review()->tugaskan($proposal->fresh(), pengguna(Role::REVIEWER), $urutan, pengguna(Role::ADMIN_LPPM));
        review()->simpan($pen, skorPenuh($proposal, ...$skor), [
            'rekomendasi' => 'didanai', 'komentar_untuk_pengusul' => $komentar." (R{$urutan})",
        ], kirim: true);
    }

    return $proposal->fresh();
}

test('proposals are ranked by the average of their two reviewers', function () {
    $periode = PeriodeHibah::factory()->create();
    $rendah = proposalDinilai($periode, [2, 2], [3, 3]);
    $tinggi = proposalDinilai($periode, [5, 5], [4, 4]);

    $peringkat = penetapan()->peringkat($periode);

    expect($peringkat->pluck('id')->all())->toBe([$tinggi->id, $rendah->id])
        ->and($peringkat->first()->rata_rata)->toBe(90.0);
});

test('the decision waits until every proposal has both reviews', function () {
    $periode = PeriodeHibah::factory()->create();
    $proposal = proposalDinilai($periode, [5, 5], []);

    penetapan()->tetapkan($periode, [$proposal->id => 1_000_000], pengguna(Role::ADMIN_LPPM));
})->throws(ValidationException::class, 'Masih ada proposal yang belum dinilai lengkap oleh dua reviewer.');

test('LPPM funds the chosen proposals and the rest are not funded', function () {
    Notification::fake();
    $periode = PeriodeHibah::factory()->create(['kuota' => 1]);
    $tinggi = proposalDinilai($periode, [5, 5], [4, 4], 'Perjelas dataset.');
    $rendah = proposalDinilai($periode, [2, 2], [3, 3]);

    $this->actingAs(pengguna(Role::ADMIN_LPPM))->post(route('admin.penetapan.store', $periode), [
        'didanai' => [$tinggi->id], 'dana' => [$tinggi->id => 4_500_000, $rendah->id => 5_000_000],
    ])->assertSessionHasNoErrors();

    $tinggi->refresh();
    $rendah->refresh();
    expect($tinggi->status)->toBe(StatusProposal::Disetujui)
        ->and($tinggi->dana_disetujui)->toBe(4_500_000)
        ->and((float) $tinggi->nilai_akhir)->toBe(90.0)
        ->and($tinggi->peringkat)->toBe(1)
        ->and($rendah->status)->toBe(StatusProposal::TidakDidanai)
        ->and($rendah->dana_disetujui)->toBeNull()
        ->and($rendah->peringkat)->toBe(2);

    Notification::assertSentTo($tinggi->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Proposal disetujui untuk didanai');
    Notification::assertSentTo($rendah->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Proposal tidak didanai');
});

test('the funded count cannot exceed the quota', function () {
    $periode = PeriodeHibah::factory()->create(['kuota' => 1]);
    $a = proposalDinilai($periode, [5, 5], [4, 4]);
    $b = proposalDinilai($periode, [4, 4], [4, 4]);

    penetapan()->tetapkan($periode, [$a->id => 1_000_000, $b->id => 1_000_000], pengguna(Role::ADMIN_LPPM));
})->throws(ValidationException::class, 'Jumlah proposal didanai melebihi kuota 1.');

test('the approved amount cannot exceed what was proposed', function () {
    $periode = PeriodeHibah::factory()->create();
    $proposal = proposalDinilai($periode, [5, 5], [4, 4]);

    penetapan()->tetapkan($periode, [$proposal->id => $proposal->dana_diusulkan + 1], pengguna(Role::ADMIN_LPPM));
})->throws(ValidationException::class);

test('the lead reads the reviewer comments without reviewer names', function () {
    $periode = PeriodeHibah::factory()->create();
    $proposal = proposalDinilai($periode, [5, 5], [4, 4], 'Perjelas dataset.');
    $namaReviewer = $proposal->penilaian()->first()->reviewer->name;
    penetapan()->tetapkan($periode, [$proposal->id => 4_000_000], pengguna(Role::ADMIN_LPPM));

    $this->actingAs($proposal->ketua->user)->get(route('proposal.show', $proposal))
        ->assertOk()
        ->assertSee('Hasil penilaian')
        ->assertSee('Perjelas dataset. (R1)')
        ->assertSee('Perjelas dataset. (R2)')
        ->assertSee('Komentar reviewer 1')
        ->assertDontSee($namaReviewer);
});

test('one SK is recorded for every approved proposal of the call', function () {
    Notification::fake();
    $periode = PeriodeHibah::factory()->create();
    $a = proposalDinilai($periode, [5, 5], [4, 4]);
    $b = proposalDinilai($periode, [4, 4], [4, 4]);
    $admin = pengguna(Role::ADMIN_LPPM);
    penetapan()->tetapkan($periode, [$a->id => 1_000_000, $b->id => 2_000_000], $admin);

    $this->actingAs($admin)->post(route('admin.penetapan.sk', $periode), ['no_sk' => '012/LPPM/X/2026', 'tgl_sk' => '2026-10-20'])
        ->assertSessionHasNoErrors();

    expect(Proposal::whereIn('id', [$a->id, $b->id])->pluck('no_sk')->unique()->all())->toBe(['012/LPPM/X/2026']);
    Notification::assertSentTo($a->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'SK pendanaan terbit');

    $this->actingAs($admin)->get(route('admin.penetapan.show', $periode))->assertOk()->assertSee('012/LPPM/X/2026');
});

test('the decision pages open for LPPM', function () {
    $periode = PeriodeHibah::factory()->create();
    proposalDinilai($periode, [5, 5], [4, 4]);
    $admin = pengguna(Role::ADMIN_LPPM);

    $this->actingAs($admin)->get(route('admin.penetapan.index'))->assertOk()->assertSee($periode->nama);
    $this->actingAs($admin)->get(route('admin.penetapan.show', $periode))->assertOk()->assertSee('Tetapkan hasil');
    $this->actingAs(pengguna(Role::DOSEN))->get(route('admin.penetapan.index'))->assertForbidden();
});
