<?php

use App\Enums\StatusProposal;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;

/**
 * A proposal forced into the given status, with the approved amount set.
 */
function proposalBerstatus(StatusProposal $status, PeriodeHibah $periode, ?Dosen $ketua = null, int $dana = 0): Proposal
{
    $proposal = Proposal::factory()->lengkap()->for($periode, 'periode')->for($ketua ?? Dosen::factory(), 'ketua')->create();
    $proposal->forceFill(['status' => $status, 'dana_disetujui' => $dana ?: null])->save();

    return $proposal;
}

test('the head reads funding and output totals for a budget year', function () {
    $prodi = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik'])
        ->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $ketua = Dosen::factory()->create(['prodi_id' => $prodi->id]);
    $periode = PeriodeHibah::factory()->create(['tahun_anggaran' => 2026, 'nama' => 'Hibah Internal 2026']);
    $lama = PeriodeHibah::factory()->create(['tahun_anggaran' => 2025, 'nama' => 'Hibah Internal 2025']);

    $berjalan = proposalBerstatus(StatusProposal::Berjalan, $periode, $ketua, 12_000_000);
    proposalBerstatus(StatusProposal::Selesai, $periode, $ketua, 8_000_000);
    proposalBerstatus(StatusProposal::TidakDidanai, $periode, $ketua);
    proposalBerstatus(StatusProposal::Draft, $periode, $ketua);
    proposalBerstatus(StatusProposal::Berjalan, $lama, $ketua, 99_000_000);
    $berjalan->pencairan()->create(['termin_ke' => 1, 'persentase' => 70, 'jumlah' => 8_400_000, 'tgl_cair' => today(), 'syarat' => 'kontrak']);
    $berjalan->luaran()->first()->update(['status_capaian' => 'terbit']);

    $this->actingAs(pengguna(Role::PIMPINAN))->get(route('pimpinan.index', ['tahun' => 2026]))
        ->assertOk()
        ->assertViewHas('jumlahUsulan', 3)
        ->assertViewHas('jumlahDidanai', 2)
        ->assertViewHas('danaDisetujui', 20_000_000)
        ->assertViewHas('danaDicairkan', 8_400_000)
        ->assertSee('Hibah Internal 2026')
        ->assertDontSee('Hibah Internal 2025')
        ->assertSee('Fakultas Teknik')
        ->assertSee('Rp 20.000.000');

    $this->actingAs(pengguna(Role::PIMPINAN))->get(route('pimpinan.index'))->assertViewHas('tahun', 2026);
});

test('the head summary opens with no grants yet', function () {
    $this->actingAs(pengguna(Role::PIMPINAN))->get(route('pimpinan.index'))->assertOk()->assertSee('Belum ada usulan pada tahun ini.');
});

test('only heads and system admins reach their own areas', function () {
    $this->actingAs(pengguna(Role::ADMIN_LPPM))->get(route('pimpinan.index'))->assertForbidden();
    $this->actingAs(pengguna(Role::PIMPINAN_UNIT))->get(route('pimpinan.index'))->assertForbidden();
    $this->actingAs(pengguna(Role::ADMIN_LPPM))->get(route('sistem.pengguna.index'))->assertForbidden();
    $this->actingAs(pengguna(Role::PIMPINAN))->get(route('sistem.pengguna.index'))->assertForbidden();

    $this->actingAs(pengguna(Role::PIMPINAN))->get(route('dashboard'))->assertSee(route('pimpinan.index'));
    $this->actingAs(pengguna(Role::ADMIN_SISTEM))->get(route('dashboard'))->assertSee(route('sistem.pengguna.index'));
});

test('guests are sent to login from both areas', function () {
    $this->get(route('pimpinan.index'))->assertRedirect('/login');
    $this->get(route('sistem.pengguna.index'))->assertRedirect('/login');
});

test('the system admin creates an account with several roles', function () {
    $admin = pengguna(Role::ADMIN_SISTEM);

    $this->actingAs($admin)->post(route('sistem.pengguna.store'), [
        'name' => 'Ketua LPPM',
        'email' => 'lppm@example.com',
        'password' => 'rahasia-panjang',
        'password_confirmation' => 'rahasia-panjang',
        'peran' => [Role::PIMPINAN, Role::REVIEWER],
    ])->assertRedirect(route('sistem.pengguna.index'));

    $user = User::where('email', 'lppm@example.com')->first();
    expect($user->hasRole(Role::PIMPINAN))->toBeTrue()
        ->and($user->hasRole(Role::REVIEWER))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue();

    $dosen = pengguna(Role::DOSEN);
    $this->actingAs($admin)->get(route('sistem.pengguna.index', ['peran' => Role::PIMPINAN]))->assertSee('Ketua LPPM')->assertDontSee($dosen->email);
    $this->actingAs($admin)->get(route('sistem.pengguna.index', ['cari' => 'lppm@']))->assertSee('Ketua LPPM');
    $this->actingAs($admin)->post(route('sistem.pengguna.store'), [
        'name' => 'Ganda', 'email' => 'lppm@example.com', 'password' => 'rahasia-panjang', 'password_confirmation' => 'rahasia-panjang', 'peran' => ['bukan_peran'],
    ])->assertSessionHasErrors(['email', 'peran.0']);
});

test('the system admin changes roles and keeps a head\'s unit', function () {
    $admin = pengguna(Role::ADMIN_SISTEM);
    $prodi = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik'])
        ->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $kaprodi = pengguna(Role::PIMPINAN_UNIT);
    $kaprodi->roles()->updateExistingPivot(Role::where('kode', Role::PIMPINAN_UNIT)->value('id'), ['prodi_id' => $prodi->id]);
    $sandiLama = $kaprodi->password;

    $this->actingAs($admin)->get(route('sistem.pengguna.edit', $kaprodi))->assertOk()->assertSee($kaprodi->email);
    $this->actingAs($admin)->put(route('sistem.pengguna.update', $kaprodi), [
        'name' => 'Kaprodi Baru',
        'email' => $kaprodi->email,
        'password' => '',
        'password_confirmation' => '',
        'peran' => [Role::PIMPINAN_UNIT, Role::DOSEN],
    ])->assertRedirect(route('sistem.pengguna.index'));

    $kaprodi = $kaprodi->fresh();
    expect($kaprodi->name)->toBe('Kaprodi Baru')
        ->and($kaprodi->password)->toBe($sandiLama)
        ->and($kaprodi->hasRole(Role::DOSEN))->toBeTrue()
        ->and($kaprodi->roles->firstWhere('kode', Role::PIMPINAN_UNIT)->pivot->prodi_id)->toBe($prodi->id);

    $this->actingAs($admin)->put(route('sistem.pengguna.update', $kaprodi), [
        'name' => $kaprodi->name, 'email' => $kaprodi->email, 'peran' => [Role::DOSEN],
    ])->assertSessionHasNoErrors();
    expect($kaprodi->fresh()->hasRole(Role::PIMPINAN_UNIT))->toBeFalse();
});

test('a system admin cannot drop their own admin role', function () {
    $admin = pengguna(Role::ADMIN_SISTEM);

    $this->actingAs($admin)->put(route('sistem.pengguna.update', $admin), [
        'name' => $admin->name, 'email' => $admin->email, 'peran' => [Role::DOSEN],
    ])->assertSessionHasErrors('peran');

    expect($admin->fresh()->hasRole(Role::ADMIN_SISTEM))->toBeTrue();
});
