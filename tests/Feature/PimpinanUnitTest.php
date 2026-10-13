<?php

use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * A head tied to a unit through the admin page.
 */
function pimpinanUnit(?string $unit): User
{
    $user = pengguna(Role::PIMPINAN_UNIT);
    if ($unit !== null) {
        test()->actingAs(pengguna(Role::ADMIN_LPPM))->put(route('admin.pimpinan-unit.update', $user), ['unit' => $unit])->assertSessionHasNoErrors();
    }

    return $user->fresh();
}

beforeEach(function () {
    $teknik = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik']);
    $this->informatika = $teknik->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $this->elektro = $teknik->programStudi()->create(['kode' => 'TE', 'nama' => 'Elektro', 'jenjang' => 'S1']);
    $this->ekonomi = Fakultas::create(['kode' => 'FE', 'nama' => 'Fakultas Ekonomi']);
    $this->manajemen = $this->ekonomi->programStudi()->create(['kode' => 'MN', 'nama' => 'Manajemen', 'jenjang' => 'S1']);

    $this->proposalTi = Proposal::factory()->lengkap()->for(Dosen::factory()->state(['prodi_id' => $this->informatika->id]), 'ketua')->create();
    alur()->kirim($this->proposalTi->fresh(), $this->proposalTi->ketua->user);
    $this->proposalTi->refresh();
});

test('a kaprodi only sees and endorses proposals of their programme', function () {
    $kaprodiTi = pimpinanUnit('prodi:'.$this->informatika->id);
    $kaprodiTe = pimpinanUnit('prodi:'.$this->elektro->id);

    $this->actingAs($kaprodiTi)->get(route('pengesahan.index'))->assertSee($this->proposalTi->judul);
    $this->actingAs($kaprodiTe)->get(route('pengesahan.index'))->assertDontSee($this->proposalTi->judul);
    $this->actingAs($kaprodiTe)->get(route('proposal.show', $this->proposalTi))->assertForbidden();

    expect(fn () => alur()->sahkan($this->proposalTi->fresh(), $kaprodiTe))
        ->toThrow(ValidationException::class, 'Proposal ini bukan dari unit yang Anda pimpin.');

    $this->actingAs($kaprodiTi)->post(route('pengesahan.sahkan', $this->proposalTi))->assertRedirect(route('pengesahan.index'));
    expect($this->proposalTi->fresh()->disahkan_oleh)->toBe($kaprodiTi->id);
});

test('a dean covers every programme of the faculty', function () {
    $dekanTeknik = pimpinanUnit('fakultas:'.$this->informatika->fakultas_id);
    $dekanEkonomi = pimpinanUnit('fakultas:'.$this->ekonomi->id);

    expect($dekanTeknik->bolehMengesahkan($this->proposalTi))->toBeTrue()
        ->and($dekanEkonomi->bolehMengesahkan($this->proposalTi))->toBeFalse();
    $this->actingAs($dekanEkonomi)->get(route('pengesahan.index'))->assertDontSee($this->proposalTi->judul);
});

test('only the heads of the lead\'s unit are notified', function () {
    Notification::fake();
    $kaprodiTi = pimpinanUnit('prodi:'.$this->informatika->id);
    $kaprodiMn = pimpinanUnit('prodi:'.$this->manajemen->id);
    $tanpaUnit = pimpinanUnit(null);
    $proposal = Proposal::factory()->lengkap()->for(Dosen::factory()->state(['prodi_id' => $this->informatika->id]), 'ketua')->create();

    alur()->kirim($proposal->fresh(), $proposal->ketua->user);

    Notification::assertSentTo($kaprodiTi, Pemberitahuan::class);
    Notification::assertSentTo($tanpaUnit, Pemberitahuan::class);
    Notification::assertNotSentTo($kaprodiMn, Pemberitahuan::class);
});

test('LPPM sets and clears a head\'s unit', function () {
    $kaprodi = pimpinanUnit('prodi:'.$this->informatika->id);
    $admin = pengguna(Role::ADMIN_LPPM);

    $this->actingAs($admin)->get(route('admin.pimpinan-unit.index'))->assertOk()->assertSee($kaprodi->name)->assertSee('Kaprodi Informatika');
    $this->actingAs($admin)->put(route('admin.pimpinan-unit.update', $kaprodi), ['unit' => ''])->assertSessionHasNoErrors();

    expect($kaprodi->fresh()->roles->first()->pivot->prodi_id)->toBeNull();
    $this->actingAs($admin)->put(route('admin.pimpinan-unit.update', $kaprodi), ['unit' => 'prodi:9999'])->assertSessionHasErrors('unit');
    $this->actingAs($admin)->put(route('admin.pimpinan-unit.update', pengguna(Role::DOSEN)), ['unit' => ''])->assertNotFound();
});
