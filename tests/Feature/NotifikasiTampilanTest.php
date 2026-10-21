<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

test('success, warning and failure flashes show as floating notices', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['status' => 'profile-updated', 'peringatan' => 'Periode hampir ditutup.', 'gagal' => 'Berkas tidak bisa dibaca.'])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('data-notifikasi', false)
        ->assertSee('Profil disimpan.')
        ->assertSee('Periode hampir ditutup.')
        ->assertSee('Berkas tidak bisa dibaca.');
});

test('validation errors show a notice that points to the fields', function () {
    $this->from('/login')->post('/login', ['email' => '', 'password' => '']);

    $this->get('/login')
        ->assertSee('Ada 2 isian yang perlu diperbaiki')
        ->assertSee('Tunjukkan kolomnya')
        ->assertSee('tandaiKolomSalah', false);
});

test('pages carry the shared confirmation dialog, used by the photo removal', function () {
    $user = User::factory()->create(['foto_profil' => 'foto-profil/contoh.jpg']);
    $user->assignRole(Role::DOSEN);

    $this->actingAs($user)->get(route('profil-dosen.edit'))
        ->assertSee('$store.konfirmasi.buka', false)
        ->assertSee('data-konfirmasi="Hapus foto profil?"', false);
});

test('a delete blocked by other records goes back with a failure notice', function () {
    Route::middleware('web')->delete('/uji-hapus', function () {
        DB::table('users')->insert(['name' => 'Ganda', 'email' => User::first()->email, 'password' => 'x']);
    });
    $user = User::factory()->create();

    $this->actingAs($user)->from('/asal')->delete('/uji-hapus')
        ->assertRedirect('/asal')
        ->assertSessionHas('gagal', 'Data tidak bisa dihapus karena masih dipakai oleh data lain.');
});

test('an expired form goes back with a warning and keeps what was typed except passwords', function () {
    Route::middleware('web')->post('/uji-kedaluwarsa', fn () => throw new TokenMismatchException);

    $this->from('/login')->post('/uji-kedaluwarsa', ['email' => 'dosen@contoh.test', 'password' => 'rahasia123'])
        ->assertRedirect('/login')
        ->assertSessionHas('peringatan')
        ->assertSessionHasInput('email', 'dosen@contoh.test')
        ->assertSessionMissing('_old_input.password');
});
