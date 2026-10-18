<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function akunDenganPeran(string $peran, array $isian = []): User
{
    $user = User::factory()->create($isian);
    $user->assignRole($peran);

    return $user;
}

test('an unverified account is sent to the verification page', function () {
    $user = akunDenganPeran(Role::DOSEN, ['email_verified_at' => null]);

    $this->actingAs($user)->get(route('pengumuman.index'))->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get(route('proposal.index'))->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get(route('karya.publikasi.index'))->assertRedirect(route('verification.notice'));
    // The profile stays open so a mistyped address can be fixed.
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    $user->markEmailAsVerified();
    $this->actingAs($user)->get(route('proposal.index'))->assertOk();
});

test('a new registration must verify its email before using the app', function () {
    $this->post('/register', [
        'name' => 'Dosen Baru',
        'email' => 'baru@example.com',
        'password' => 'rahasia-panjang',
        'password_confirmation' => 'rahasia-panjang',
    ]);

    expect(User::where('email', 'baru@example.com')->first()->hasVerifiedEmail())->toBeFalse();
    $this->get(route('proposal.index'))->assertRedirect(route('verification.notice'));
});

test('an account with a password set by someone else must change it first', function () {
    $user = akunDenganPeran(Role::ADMIN_SISTEM, ['wajib_ganti_password' => true]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('password.wajib'));
    $this->actingAs($user)->get(route('sistem.pengguna.index'))->assertRedirect(route('password.wajib'));
    $this->actingAs($user)->get(route('password.wajib'))->assertOk()->assertSee('Ganti kata sandi');

    // The new password must differ from the one handed over.
    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'password',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrorsIn('updatePassword', 'password');

    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'password',
        'password' => 'sandi-baru-saya',
        'password_confirmation' => 'sandi-baru-saya',
    ])->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->wajib_ganti_password)->toBeFalse()
        ->and(Hash::check('sandi-baru-saya', $user->password))->toBeTrue();
    $this->actingAs($user)->get(route('sistem.pengguna.index'))->assertOk();
    $this->actingAs($user)->get(route('password.wajib'))->assertRedirect(route('dashboard'));
});

test('a forced user can still log out', function () {
    $user = akunDenganPeran(Role::DOSEN, ['wajib_ganti_password' => true]);

    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();
});

test('accounts the system admin creates or resets must change their password', function () {
    $admin = akunDenganPeran(Role::ADMIN_SISTEM);

    $this->actingAs($admin)->post(route('sistem.pengguna.store'), [
        'name' => 'Operator Baru',
        'email' => 'operator-baru@example.com',
        'password' => 'rahasia-panjang',
        'password_confirmation' => 'rahasia-panjang',
        'peran' => [Role::OPERATOR],
    ])->assertRedirect(route('sistem.pengguna.index'));
    expect(User::where('email', 'operator-baru@example.com')->first()->wajib_ganti_password)->toBeTrue();

    $dosen = akunDenganPeran(Role::DOSEN);
    $this->actingAs($admin)->put(route('sistem.pengguna.update', $dosen), [
        'name' => $dosen->name,
        'email' => $dosen->email,
        'password' => 'sandi-sementara',
        'password_confirmation' => 'sandi-sementara',
        'peran' => [Role::DOSEN],
    ])->assertRedirect(route('sistem.pengguna.index'));
    expect($dosen->refresh()->wajib_ganti_password)->toBeTrue();

    // Editing a name only leaves the flag alone.
    $lain = akunDenganPeran(Role::DOSEN);
    $this->actingAs($admin)->put(route('sistem.pengguna.update', $lain), [
        'name' => 'Nama Baru',
        'email' => $lain->email,
        'password' => '',
        'password_confirmation' => '',
        'peran' => [Role::DOSEN],
    ]);
    expect($lain->refresh()->wajib_ganti_password)->toBeFalse();
});

test('password fields have a show/hide button', function () {
    $this->get('/login')->assertOk()->assertSee('Tampilkan kata sandi')->assertSee(':type="lihat ? \'text\' : \'password\'"', false);
    $this->get('/register')->assertSee('Tampilkan kata sandi');

    $user = akunDenganPeran(Role::DOSEN);
    $this->actingAs($user)->get(route('profile.edit'))->assertSee('Tampilkan kata sandi');
});
