<?php

use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Dosen;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\SkemaHibah;
use App\Models\User;
use App\Services\AlurProposal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function akun(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('a lecturer without a profile is asked to complete it first', function () {
    $periode = PeriodeHibah::factory()->create();

    $this->actingAs(akun(Role::DOSEN))
        ->get(route('proposal.create', $periode))
        ->assertRedirect(route('profil-dosen.edit'));
});

test('a lecturer saves their profile', function () {
    $user = akun(Role::DOSEN);

    $this->actingAs($user)->put(route('profil-dosen.update'), [
        'nama' => 'Ani Lestari', 'nidn' => '0123456789', 'sinta_id' => '6012345',
    ])->assertRedirect(route('profil-dosen.edit'));

    expect($user->fresh()->dosen->identitasLengkap())->toBeTrue();
});

test('a lecturer opens a call and creates a draft', function () {
    $dosen = Dosen::factory()->create();
    $periode = PeriodeHibah::factory()->create();

    $this->actingAs($dosen->user)->get(route('proposal.index'))->assertOk()->assertSee($periode->nama);

    $this->actingAs($dosen->user)->post(route('proposal.store', $periode), [
        'judul' => 'Deteksi dini penyakit padi dengan citra daun',
        'ringkasan' => 'Ringkasan penelitian.',
    ])->assertRedirect();

    $proposal = Proposal::sole();
    expect($proposal->ketua_id)->toBe($dosen->id)
        ->and($proposal->status)->toBe(StatusProposal::Draft);

    $this->actingAs($dosen->user)->get(route('proposal.show', $proposal))
        ->assertOk()
        ->assertSee('Kelengkapan sebelum dikirim')
        ->assertSee('Unggah substansi proposal');
});

test('a closed call cannot be applied to', function () {
    $dosen = Dosen::factory()->create();
    $periode = PeriodeHibah::factory()->ditutup()->create();

    $this->actingAs($dosen->user)->get(route('proposal.create', $periode))->assertNotFound();
});

test('the lead fills in the budget, outputs and substance, then sends', function () {
    Storage::fake();
    $proposal = Proposal::factory()->create();
    $ketua = $proposal->ketua->user;

    $this->actingAs($ketua)->post(route('proposal.rab.store', $proposal), [
        'kategori' => 'bahan', 'uraian' => 'Bahan uji', 'volume' => 2, 'satuan' => 'paket', 'harga_satuan' => 1_500_000,
    ])->assertSessionHasNoErrors();
    $this->actingAs($ketua)->post(route('proposal.luaran.store', $proposal), [
        'kategori' => 'wajib', 'jenis' => 'artikel_jurnal', 'target_keterangan' => 'Jurnal Sinta 3',
    ])->assertSessionHasNoErrors();
    $this->actingAs($ketua)->post(route('proposal.substansi.store', $proposal), [
        'berkas' => UploadedFile::fake()->create('substansi.pdf', 200, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    expect($proposal->fresh()->totalRab())->toBe(3_000_000);
    Storage::assertExists($proposal->fresh()->berkasSubstansi->path);

    $this->actingAs($ketua)->post(route('proposal.kirim', $proposal))->assertSessionHasNoErrors();

    expect($proposal->fresh()->menungguPengesahan())->toBeTrue();

    $this->actingAs($ketua)->post(route('proposal.rab.store', $proposal), [
        'kategori' => 'bahan', 'uraian' => 'Tambahan', 'volume' => 1, 'satuan' => 'paket', 'harga_satuan' => 1,
    ])->assertForbidden();
});

test('honorarium lines are refused when the call does not allow them', function () {
    $proposal = Proposal::factory()->create();

    $this->actingAs($proposal->ketua->user)->post(route('proposal.rab.store', $proposal), [
        'kategori' => 'honorarium', 'uraian' => 'Honor', 'volume' => 1, 'satuan' => 'OB', 'harga_satuan' => 100_000,
    ])->assertSessionHasErrors('kategori');
});

test('only PDF substance files are accepted', function () {
    Storage::fake();
    $proposal = Proposal::factory()->create();

    $this->actingAs($proposal->ketua->user)->post(route('proposal.substansi.store', $proposal), [
        'berkas' => UploadedFile::fake()->create('substansi.docx', 100),
    ])->assertSessionHasErrors('berkas');
});

test('an invited lecturer confirms through the invitation', function () {
    $proposal = Proposal::factory()->create();
    $anggota = Dosen::factory()->create();

    $this->actingAs($proposal->ketua->user)->post(route('proposal.anggota.store', $proposal), [
        'tipe' => 'dosen', 'nidn' => $anggota->nidn, 'peran' => 'Analis data',
    ])->assertSessionHasNoErrors();

    $undangan = $proposal->anggota()->sole();
    expect($undangan->tipe)->toBe(TipeAnggota::Dosen)->and($undangan->dosen_id)->toBe($anggota->id);

    $this->actingAs(Dosen::factory()->create()->user)->get(route('undangan.show', $undangan->token_undangan))->assertForbidden();

    $this->actingAs($anggota->user)->get(route('proposal.index'))->assertSee('Jawab undangan');
    $this->actingAs($anggota->user)->post(route('undangan.jawab', $undangan->token_undangan), ['jawaban' => 'terima'])
        ->assertRedirect(route('proposal.index'));

    expect($undangan->fresh()->dikonfirmasi_at)->not->toBeNull();
    $this->actingAs($anggota->user)->get(route('proposal.show', $proposal))->assertOk();
});

test('an unknown NIDN cannot be invited', function () {
    $proposal = Proposal::factory()->create();

    $this->actingAs($proposal->ketua->user)->post(route('proposal.anggota.store', $proposal), [
        'tipe' => 'dosen', 'nidn' => '9999999999',
    ])->assertSessionHasErrors('nidn');
});

test('outsiders and reviewers cannot open a proposal', function () {
    $proposal = Proposal::factory()->create();

    $this->actingAs(Dosen::factory()->create()->user)->get(route('proposal.show', $proposal))->assertForbidden();
    $this->actingAs(akun(Role::REVIEWER))->get(route('proposal.show', $proposal))->assertForbidden();
    $this->actingAs(Dosen::factory()->create()->user)->put(route('proposal.update', $proposal), ['judul' => 'x', 'ringkasan' => 'y'])->assertForbidden();
});

test('a faculty head endorses from the endorsement list', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    app(AlurProposal::class)->kirim($proposal->fresh(), $proposal->ketua->user);
    $kaprodi = akun(Role::PIMPINAN_UNIT);

    $this->actingAs($kaprodi)->get(route('pengesahan.index'))->assertOk()->assertSee($proposal->judul);
    $this->actingAs($kaprodi)->get(route('proposal.show', $proposal))->assertOk()->assertSee('Sahkan');
    $this->actingAs($kaprodi)->post(route('pengesahan.sahkan', $proposal))->assertRedirect(route('pengesahan.index'));

    expect($proposal->fresh()->status)->toBe(StatusProposal::Diajukan);

    $this->actingAs($proposal->ketua->user)->post(route('pengesahan.sahkan', $proposal))->assertForbidden();
});

test('LPPM screens an endorsed proposal', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    app(AlurProposal::class)->kirim($proposal->fresh(), $proposal->ketua->user);
    app(AlurProposal::class)->sahkan($proposal->fresh(), akun(Role::PIMPINAN_UNIT));
    $admin = akun(Role::ADMIN_LPPM);

    $this->actingAs($admin)->get(route('admin.seleksi.index'))->assertOk()->assertSee($proposal->judul);
    $this->actingAs($admin)->post(route('admin.seleksi.kembalikan', $proposal), [
        'catatan' => 'Lampirkan surat pernyataan', 'batas_perbaikan' => today()->addWeek()->toDateString(),
    ])->assertSessionHasNoErrors();

    expect($proposal->fresh()->status)->toBe(StatusProposal::Dikembalikan);

    $this->actingAs($proposal->ketua->user)->get(route('proposal.show', $proposal))
        ->assertSee('Lampirkan surat pernyataan')
        ->assertSee('Kirim perbaikan ke LPPM');
});

test('LPPM manages schemes and yearly calls', function () {
    $admin = akun(Role::ADMIN_LPPM);

    $this->actingAs($admin)->post(route('admin.skema.store'), [
        'kode' => 'PT', 'nama' => 'Penelitian Terapan', 'jenis_kegiatan' => 'penelitian', 'sumber_dana' => 'internal',
        'lama_maks_tahun' => 3, 'is_multitahun' => '1', 'dana_maksimal_default' => 50_000_000,
        'honor_diizinkan' => '1', 'batas_honor_persen' => 10, 'is_active' => '1',
    ])->assertRedirect(route('admin.skema.index'));

    $skema = SkemaHibah::where('kode', 'PT')->sole();
    expect($skema->is_multitahun)->toBeTrue()->and($skema->lama_maks_tahun)->toBe(3);

    $this->actingAs($admin)->get(route('admin.periode.create', ['skema_id' => $skema->id]))
        ->assertOk()->assertSee('50000000');

    $this->actingAs($admin)->post(route('admin.periode.store'), [
        'skema_id' => $skema->id, 'tahun_anggaran' => 2027, 'nama' => 'Penelitian Terapan 2027',
        'tgl_buka' => '2027-01-10', 'tgl_tutup' => '2027-02-10', 'dana_maksimal' => 45_000_000,
        'termin_pertama_persen' => 80, 'maks_sebagai_ketua' => 1, 'maks_sebagai_anggota' => 2, 'status' => 'draft',
    ])->assertRedirect(route('admin.periode.index'));

    expect(PeriodeHibah::where('nama', 'Penelitian Terapan 2027')->sole()->dana_maksimal)->toBe(45_000_000);

    $this->actingAs($admin)->get(route('admin.skema.index'))->assertOk()->assertSee('Penelitian Terapan');
    $this->actingAs($admin)->get(route('admin.periode.index'))->assertOk()->assertSee('Penelitian Terapan 2027');
    $this->actingAs(akun(Role::DOSEN))->get(route('admin.skema.index'))->assertForbidden();
});

test('the seeded data shows an open call', function () {
    $this->seed();

    $dosen = User::where('email', 'dosen@example.com')->sole();

    $this->actingAs($dosen)->get(route('proposal.index'))->assertOk()->assertSee('Penelitian Dosen Pemula');
});
