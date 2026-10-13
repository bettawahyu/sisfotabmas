<?php

use App\Enums\StatusProposal;
use App\Models\PencairanDana;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\Role;
use App\Notifications\Pemberitahuan;
use App\Services\AlurPencairan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * An approved proposal with Rp 4.500.000 approved and an SK on record.
 */
function proposalDisetujui(): Proposal
{
    $periode = PeriodeHibah::factory()->create();
    $proposal = proposalDinilai($periode, [5, 5], [4, 4]);
    $admin = pengguna(Role::ADMIN_LPPM);
    penetapan()->tetapkan($periode, [$proposal->id => 4_500_000], $admin);
    penetapan()->terbitkanSk($periode, '012/LPPM/X/2026', today());

    return $proposal->fresh();
}

$kontrak = ['no_kontrak' => '045/KONTRAK/2026', 'tgl_kontrak' => '2026-10-21', 'tgl_mulai' => '2026-11-01', 'tgl_selesai' => '2027-08-31'];

test('LPPM records the contract and the proposal becomes funded', function () use ($kontrak) {
    Notification::fake();
    $keuangan = pengguna(Role::KEUANGAN);
    $proposal = proposalDisetujui();

    $this->actingAs(pengguna(Role::ADMIN_LPPM))->get(route('admin.kontrak.index'))->assertOk()->assertSee($proposal->judul);
    $this->actingAs(pengguna(Role::ADMIN_LPPM))->post(route('admin.kontrak.store', $proposal), $kontrak)->assertSessionHasNoErrors();

    $proposal->refresh();
    expect($proposal->status)->toBe(StatusProposal::Didanai)
        ->and($proposal->no_kontrak)->toBe('045/KONTRAK/2026');
    Notification::assertSentTo($keuangan, Pemberitahuan::class, fn ($n) => $n->judul === 'Termin 1 siap dicairkan');
    Notification::assertSentTo($proposal->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Kontrak penelitian dicatat');
});

test('a contract needs the SK first', function () use ($kontrak) {
    $periode = PeriodeHibah::factory()->create();
    $proposal = proposalDinilai($periode, [5, 5], [4, 4]);
    penetapan()->tetapkan($periode, [$proposal->id => 4_500_000], pengguna(Role::ADMIN_LPPM));

    $this->actingAs(pengguna(Role::ADMIN_LPPM))->post(route('admin.kontrak.store', $proposal), $kontrak)
        ->assertSessionHasErrors('pencairan');
    expect($proposal->fresh()->status)->toBe(StatusProposal::Disetujui);
});

test('finance records tranche one at 80 percent with proof and the project starts', function () use ($kontrak) {
    Storage::fake();
    Notification::fake();
    $proposal = proposalDisetujui();
    $this->actingAs(pengguna(Role::ADMIN_LPPM))->post(route('admin.kontrak.store', $proposal), $kontrak);
    $keuangan = pengguna(Role::KEUANGAN);

    $this->actingAs($keuangan)->get(route('pencairan.index'))->assertOk()->assertSee($proposal->judul)->assertSee('Catat termin 1');
    $this->actingAs($keuangan)->post(route('pencairan.store', $proposal), [
        'tgl_cair' => today()->toDateString(), 'pajak_lunas' => '1',
        'bukti' => UploadedFile::fake()->create('transfer.pdf', 20, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $pencairan = PencairanDana::sole();
    expect($pencairan->termin_ke)->toBe(1)
        ->and($pencairan->persentase)->toBe(80)
        ->and($pencairan->jumlah)->toBe(3_600_000)
        ->and($pencairan->pajak_lunas)->toBeTrue()
        ->and($pencairan->bukti->nama_asli)->toBe('transfer.pdf')
        ->and($proposal->fresh()->status)->toBe(StatusProposal::Berjalan);
    Notification::assertSentTo($proposal->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Termin 1 dicairkan');

    $this->actingAs($keuangan)->get(route('pencairan.bukti', $pencairan))->assertDownload('transfer.pdf');
    $this->actingAs($proposal->ketua->user)->get(route('proposal.show', $proposal))->assertSee('045/KONTRAK/2026')->assertSee('Rp 3.600.000');
});

test('the two tranches always add up to the approved amount', function () {
    $proposal = proposalDisetujui();
    $proposal->forceFill(['dana_disetujui' => 4_999_999])->save();
    $alur = app(AlurPencairan::class);

    expect($alur->jumlahTermin($proposal, 1) + $alur->jumlahTermin($proposal, 2))->toBe(4_999_999);
});

test('LPPM admins see disbursements but cannot record them', function () use ($kontrak) {
    Storage::fake();
    $proposal = proposalDisetujui();
    $admin = pengguna(Role::ADMIN_LPPM);
    $this->actingAs($admin)->post(route('admin.kontrak.store', $proposal), $kontrak);

    $this->actingAs($admin)->get(route('pencairan.index'))->assertOk()->assertSee($proposal->judul)->assertDontSee('Catat termin 1');
    $this->actingAs($admin)->post(route('pencairan.store', $proposal), [
        'tgl_cair' => today()->toDateString(), 'bukti' => UploadedFile::fake()->create('transfer.pdf', 20, 'application/pdf'),
    ])->assertForbidden();
    expect(PencairanDana::count())->toBe(0);

    $this->actingAs(pengguna(Role::DOSEN))->get(route('pencairan.index'))->assertForbidden();
});

test('tranche one cannot be recorded before the contract', function () {
    Storage::fake();
    $proposal = proposalDisetujui();

    $this->actingAs(pengguna(Role::KEUANGAN))->post(route('pencairan.store', $proposal), [
        'tgl_cair' => today()->toDateString(), 'bukti' => UploadedFile::fake()->create('transfer.pdf', 20, 'application/pdf'),
    ])->assertSessionHasErrors('pencairan');
});
