<?php

use App\Enums\JenisKegiatan;
use App\Enums\KategoriRab;
use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Dosen;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\SkemaHibah;
use App\Models\User;
use App\Services\AlurProposal;
use Illuminate\Validation\ValidationException;

function alur(): AlurProposal
{
    return app(AlurProposal::class);
}

function pengguna(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * The messages that stop the proposal from being sent.
 *
 * @return list<string>
 */
function kekurangan(Proposal $proposal): array
{
    return alur()->kekurangan($proposal->fresh());
}

test('a complete draft can be sent and then waits for endorsement', function () {
    $proposal = Proposal::factory()->lengkap()->create();

    alur()->kirim($proposal->fresh(), $proposal->ketua->user);

    $proposal->refresh();
    expect($proposal->status)->toBe(StatusProposal::Draft)
        ->and($proposal->menungguPengesahan())->toBeTrue()
        ->and($proposal->bisaDiubah())->toBeFalse()
        ->and($proposal->dana_diusulkan)->toBe(5_000_000)
        ->and($proposal->riwayatStatus()->count())->toBe(1);
});

test('a draft without substance, budget or mandatory output cannot be sent', function () {
    $proposal = Proposal::factory()->create();

    expect(kekurangan($proposal))->toContain(
        'Unggah substansi proposal (PDF dari templat LPPM).',
        'Isi rencana anggaran belanja (RAB).',
        'Tambahkan minimal satu luaran wajib.',
    );

    alur()->kirim($proposal->fresh(), $proposal->ketua->user);
})->throws(ValidationException::class);

test('the lead needs NIDN or NUPTK and a SINTA ID', function () {
    $proposal = Proposal::factory()->lengkap()->for(Dosen::factory()->tanpaIdentitas(), 'ketua')->create();

    expect(kekurangan($proposal))->toContain('Lengkapi NIDN atau NUPTK dan ID SINTA ketua di profil dosen.');
});

test('a closed call does not accept proposals', function () {
    $proposal = Proposal::factory()->lengkap()->for(PeriodeHibah::factory()->ditutup(), 'periode')->create();

    expect(kekurangan($proposal))->toContain('Periode hibah ini tidak sedang menerima pengajuan.');
});

test('every lecturer member must confirm first', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    $anggota = Dosen::factory()->create();
    $proposal->anggota()->create(['tipe' => TipeAnggota::Dosen, 'dosen_id' => $anggota->id, 'nama' => $anggota->nama]);

    expect(kekurangan($proposal))->toContain('Semua anggota dosen harus mengonfirmasi kesediaan.');

    $proposal->anggota()->first()->forceFill(['dikonfirmasi_at' => now()])->save();

    expect(kekurangan($proposal))->toBe([]);
});

test('student and external members need no confirmation', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    $proposal->anggota()->create(['tipe' => TipeAnggota::Mahasiswa, 'nama' => 'Siti', 'nim' => '2201001']);

    expect(kekurangan($proposal))->toBe([]);
});

test('the budget must stay under the call ceiling', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    $proposal->rab()->create([
        'kategori' => KategoriRab::SewaPeralatan, 'uraian' => 'Sewa server', 'volume' => 1,
        'satuan' => 'paket', 'harga_satuan' => 11_000_000, 'total' => 11_000_000,
    ]);

    expect(kekurangan($proposal))->toContain('Total RAB Rp 16.000.000 melebihi plafon Rp 15.000.000.');
});

test('honorarium is refused unless the call enables it', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    $proposal->rab()->create([
        'kategori' => KategoriRab::Honorarium, 'uraian' => 'Honor pembantu peneliti', 'volume' => 1,
        'satuan' => 'OB', 'harga_satuan' => 500_000, 'total' => 500_000,
    ]);

    expect(kekurangan($proposal))->toContain('Periode ini tidak mengizinkan biaya honorarium.');

    $proposal->periode->update(['honor_diizinkan' => true, 'batas_honor_persen' => 5]);

    expect(kekurangan($proposal))->toContain('Biaya honorarium melebihi 5% dari total RAB.');

    $proposal->periode->update(['batas_honor_persen' => 10]);

    expect(kekurangan($proposal))->toBe([]);
});

test('community service proposals need a partner', function () {
    $periode = PeriodeHibah::factory()->for(SkemaHibah::factory()->pkm(), 'skema')->create();
    $proposal = Proposal::factory()->lengkap()->for($periode, 'periode')->create();

    expect($periode->skema->jenis_kegiatan)->toBe(JenisKegiatan::Pkm)
        ->and(kekurangan($proposal))->toContain('Proposal PkM wajib mencantumkan minimal satu mitra.');

    $proposal->mitra()->create(['nama_mitra' => 'Kelompok Tani Makmur', 'jenis_mitra' => 'Kelompok masyarakat']);

    expect(kekurangan($proposal))->toBe([]);
});

test('a lecturer leads at most the allowed number of proposals per call', function () {
    $pertama = Proposal::factory()->lengkap()->create();
    alur()->kirim($pertama->fresh(), $pertama->ketua->user);

    $kedua = Proposal::factory()->lengkap()->create(['periode_id' => $pertama->periode_id, 'ketua_id' => $pertama->ketua_id]);

    expect(kekurangan($kedua))->toContain('Ketua sudah mengajukan 1 proposal sebagai ketua di periode ini (maksimal 1).');
});

test('endorsement moves the proposal to LPPM', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);
    $kaprodi = pengguna(Role::PIMPINAN_UNIT);

    alur()->sahkan($proposal->fresh(), $kaprodi);

    $proposal->refresh();
    expect($proposal->status)->toBe(StatusProposal::Diajukan)
        ->and($proposal->disahkan_oleh)->toBe($kaprodi->id)
        ->and($proposal->diajukan_at)->not->toBeNull();
});

test('a refused endorsement gives the draft back to the lead', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);

    alur()->tolakPengesahan($proposal->fresh(), pengguna(Role::PIMPINAN_UNIT), 'Perbaiki judul');

    expect($proposal->fresh()->bisaDiubah())->toBeTrue();
});

test('LPPM returns a proposal and the lead sends the fix straight back', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);
    alur()->sahkan($proposal->fresh(), pengguna(Role::PIMPINAN_UNIT));
    $admin = pengguna(Role::ADMIN_LPPM);

    alur()->kembalikan($proposal->fresh(), $admin, 'Halaman melebihi batas', now()->addWeek());

    expect($proposal->fresh()->status)->toBe(StatusProposal::Dikembalikan)
        ->and($proposal->fresh()->bisaDiubah())->toBeTrue();

    alur()->kirim($proposal->fresh(), $proposal->ketua->user);

    expect($proposal->fresh()->status)->toBe(StatusProposal::Diajukan)
        ->and($proposal->fresh()->batas_perbaikan_at)->toBeNull();

    alur()->lolosAdministrasi($proposal->fresh(), $admin);

    expect($proposal->fresh()->status)->toBe(StatusProposal::Ditinjau);
});

test('a returned proposal past its deadline is closed as not funded', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);
    alur()->sahkan($proposal->fresh(), pengguna(Role::PIMPINAN_UNIT));
    alur()->kembalikan($proposal->fresh(), pengguna(Role::ADMIN_LPPM), 'Lengkapi surat', now()->addDay());

    $this->travel(2)->days();

    expect(kekurangan($proposal))->toContain('Batas waktu perbaikan sudah lewat.')
        ->and(alur()->tutupYangLewatBatas())->toBe(1)
        ->and($proposal->fresh()->status)->toBe(StatusProposal::TidakDidanai);
});

test('a status cannot skip a step', function () {
    $proposal = Proposal::factory()->lengkap()->create();

    alur()->lolosAdministrasi($proposal->fresh(), pengguna(Role::ADMIN_LPPM));
})->throws(ValidationException::class);
