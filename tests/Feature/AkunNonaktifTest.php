<?php

use App\Models\Dosen;
use App\Models\Role;
use App\Models\User;

function akunUntukNonaktif(string $peran): User
{
    $user = User::factory()->create();
    $user->assignRole($peran);

    return $user;
}

test('the system admin switches any account off and on, but not their own', function () {
    $admin = akunUntukNonaktif(Role::ADMIN_SISTEM);
    $operator = akunUntukNonaktif(Role::OPERATOR);

    $this->actingAs($admin)->get(route('sistem.pengguna.index'))->assertSee('Nonaktifkan')
        ->assertSee(route('sistem.pengguna.aktif', $operator), false)
        ->assertDontSee(route('sistem.pengguna.aktif', $admin), false);

    $this->actingAs($admin)->patch(route('sistem.pengguna.aktif', $operator), ['aktif' => 0])->assertSessionHas('status');
    expect($operator->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch(route('sistem.pengguna.aktif', $operator), ['aktif' => 1]);
    expect($operator->fresh()->is_active)->toBeTrue();

    $this->actingAs($admin)->patch(route('sistem.pengguna.aktif', $admin), ['aktif' => 0])->assertSessionHas('gagal');
    expect($admin->fresh()->is_active)->toBeTrue();

    $this->actingAs($operator->fresh())->patch(route('sistem.pengguna.aktif', $admin), ['aktif' => 0])->assertForbidden();
});

test('LPPM switches a lecturer account off from Data Dosen; others cannot', function () {
    $lppm = akunUntukNonaktif(Role::ADMIN_LPPM);
    $dosen = Dosen::factory()->create(['nama' => 'Ani']);

    $this->actingAs($lppm)->get(route('data-dosen.index'))->assertSee(route('data-dosen.aktif', $dosen), false);
    $this->actingAs($lppm)->patch(route('data-dosen.aktif', $dosen), ['aktif' => 0])->assertSessionHas('status');
    expect($dosen->user->fresh()->is_active)->toBeFalse();

    $operator = akunUntukNonaktif(Role::OPERATOR);
    $this->actingAs($operator)->get(route('data-dosen.index'))->assertDontSee(route('data-dosen.aktif', $dosen), false);
    $this->actingAs($operator)->patch(route('data-dosen.aktif', $dosen), ['aktif' => 1])->assertForbidden();
    expect($dosen->user->fresh()->is_active)->toBeFalse();
});

test('a switched-off account cannot sign in and is signed out if already in', function () {
    $user = akunUntukNonaktif(Role::OPERATOR);
    $user->aturAktif(false);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();

    // The records stay: switching off is not deleting.
    expect(User::find($user->id))->not->toBeNull();
});
