<?php

use App\Models\Role;
use App\Models\User;

test('the role list ships with the schema', function () {
    expect(Role::pluck('kode')->sort()->values()->all())
        ->toBe(collect(array_keys(Role::ALL))->sort()->values()->all());
});

test('a user can hold several roles', function () {
    $user = User::factory()->create();

    $user->assignRole(Role::DOSEN, Role::REVIEWER);

    expect($user->hasRole(Role::DOSEN))->toBeTrue()
        ->and($user->hasRole(Role::REVIEWER))->toBeTrue()
        ->and($user->hasRole(Role::ADMIN_LPPM))->toBeFalse()
        ->and($user->hasRole(Role::ADMIN_LPPM, Role::REVIEWER))->toBeTrue();
});

test('assigning a role twice keeps one row', function () {
    $user = User::factory()->create();

    $user->assignRole(Role::DOSEN);
    $user->assignRole(Role::DOSEN);

    expect($user->roles()->count())->toBe(1);
});

test('new registrations become dosen', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::where('email', 'test@example.com')->first()->hasRole(Role::DOSEN))->toBeTrue();
});

test('admin lppm can open the admin area', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::ADMIN_LPPM);

    $this->actingAs($user)->get('/admin')->assertOk();
});

test('other roles are refused the admin area', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::DOSEN, Role::REVIEWER);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('guests are sent to login from the admin area', function () {
    $this->get('/admin')->assertRedirect('/login');
});
