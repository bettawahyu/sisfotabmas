<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('profile photo can be uploaded, shown, replaced and removed', function () {
    Storage::fake();
    $user = User::factory()->create(['name' => 'Rina Hartati']);

    $this->actingAs($user)->get('/dashboard')->assertSee('RH');

    $this->actingAs($user)
        ->post('/profile/foto', ['foto' => UploadedFile::fake()->image('foto.jpg', 300, 300)])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $pertama = $user->refresh()->foto_profil;
    Storage::assertExists($pertama);
    $this->actingAs($user)->get('/profile')->assertSee(route('foto-profil.show', $user));
    $this->actingAs(User::factory()->create())->get(route('foto-profil.show', $user))->assertOk();

    $this->actingAs($user)->post('/profile/foto', ['foto' => UploadedFile::fake()->image('baru.png', 200, 200)]);
    Storage::assertMissing($pertama);
    Storage::assertExists($user->refresh()->foto_profil);

    $kedua = $user->foto_profil;
    $this->actingAs($user)->delete('/profile/foto')->assertRedirect('/profile');
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
        ->assertRedirect('/profile');

    expect($user->refresh()->foto_profil)->toBeNull();
});

test('guests cannot see profile photos', function () {
    $user = User::factory()->create();

    $this->get(route('foto-profil.show', $user))->assertRedirect('/login');
});
