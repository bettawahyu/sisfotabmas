<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the account page shows the login email as text and the password form only', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertRedirect('/akun');
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSee($user->email)
        ->assertSee('update_password_current_password', false)
        ->assertDontSee('name="email"', false)
        ->assertDontSee('name="name"', false);
});

test('users cannot change their name or email, nor delete their own account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/akun', ['name' => 'Lain', 'email' => 'lain@example.com'])->assertStatus(405);
    $this->actingAs($user)->delete('/akun', ['password' => 'password'])->assertStatus(405);

    expect($user->fresh())->not->toBeNull()->and($user->fresh()->email)->not->toBe('lain@example.com');
});

test('profile photo can be uploaded, shown, replaced and removed', function () {
    Storage::fake();
    $user = User::factory()->create(['name' => 'Rina Hartati']);

    $this->actingAs($user)->get('/dashboard')->assertSee('RH');

    $this->actingAs($user)
        ->post('/profile/foto', ['foto' => UploadedFile::fake()->image('foto.jpg', 300, 300)])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $pertama = $user->refresh()->foto_profil;
    Storage::assertExists($pertama);
    $this->actingAs($user)->get('/dashboard')->assertSee(route('foto-profil.show', $user));
    $this->actingAs(User::factory()->create())->get(route('foto-profil.show', $user))->assertOk();

    $this->actingAs($user)->post('/profile/foto', ['foto' => UploadedFile::fake()->image('baru.png', 200, 200)]);
    Storage::assertMissing($pertama);
    Storage::assertExists($user->refresh()->foto_profil);

    $kedua = $user->foto_profil;
    $this->actingAs($user)->delete('/profile/foto')->assertRedirect(route('profile.edit'));
    Storage::assertMissing($kedua);
    expect($user->refresh()->foto_profil)->toBeNull();
    $this->actingAs($user)->get(route('foto-profil.show', $user))->assertNotFound();
});

test('profile photo must be an image', function () {
    Storage::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profile')
        ->post('/profile/foto', ['foto' => UploadedFile::fake()->create('berkas.pdf', 100, 'application/pdf')])
        ->assertSessionHasErrors('foto')
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->foto_profil)->toBeNull();
});

test('guests cannot see profile photos', function () {
    $user = User::factory()->create();

    $this->get(route('foto-profil.show', $user))->assertRedirect('/login');
});
