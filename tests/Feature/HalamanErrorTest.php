<?php

use App\Models\Role;
use App\Models\User;
use App\Services\IdentitasAplikasi;
use Illuminate\Support\Facades\Route;

test('an unknown address shows the custom not found page with the app name', function () {
    app(IdentitasAplikasi::class)->simpan(['nama' => 'SIMLITABMAS Poltekom'], [], User::factory()->create());

    $this->get('/alamat-yang-tidak-ada')
        ->assertNotFound()
        ->assertSee('Halaman yang Anda cari tidak ada')
        ->assertSee('SIMLITABMAS Poltekom')
        ->assertSee('Ke Beranda');
});

test('a page outside the user role shows the custom forbidden page', function () {
    $dosen = User::factory()->create();
    $dosen->assignRole(Role::DOSEN);

    $this->actingAs($dosen)->get(route('sistem.identitas.edit'))
        ->assertForbidden()
        ->assertSee('Halaman ini bukan untuk akun Anda')
        ->assertSee('Ke Dashboard');
});

test('server errors and maintenance show their own pages', function () {
    Route::get('/uji-500', fn () => abort(500));
    Route::get('/uji-503', fn () => abort(503, '', ['Retry-After' => 600]));

    $this->get('/uji-500')
        ->assertStatus(500)
        ->assertSee('Terjadi kesalahan di sistem kami')
        ->assertSee('Muat ulang');

    $this->get('/uji-503')
        ->assertStatus(503)
        ->assertSee('Sistem sedang dirawat sebentar')
        ->assertSee('10 menit');
});
