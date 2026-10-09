<?php

use App\Models\User;

test('the app speaks Indonesian by default', function () {
    expect(app()->getLocale())->toBe('id');

    $this->get('/login')->assertOk()->assertSee('Lupa kata sandi?');
});

test('validation messages are in Indonesian', function () {
    $this->post('/register', [])->assertSessionHasErrors([
        'email' => 'Isian email wajib diisi.',
        'password' => 'Isian kata sandi wajib diisi.',
    ]);
});

test('failed login explains itself in Indonesian', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
        ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai dengan data kami.']);
});
