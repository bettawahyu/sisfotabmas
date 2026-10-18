<?php

use App\Models\Pengaturan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake();
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN_SISTEM);
});

function simpanIdentitas(array $isian = []): TestResponse
{
    return test()->actingAs(test()->admin)->put(route('sistem.identitas.update'), $isian + ['nama' => 'SIMLITABMAS Poltekom']);
}

test('only the system admin changes the app identity', function () {
    foreach ([Role::ADMIN_LPPM, Role::OPERATOR, Role::DOSEN] as $peran) {
        $lain = User::factory()->create();
        $lain->assignRole($peran);
        $this->actingAs($lain)->get(route('sistem.identitas.edit'))->assertForbidden();
        $this->actingAs($lain)->put(route('sistem.identitas.update'), ['nama' => 'Bukan Admin'])->assertForbidden();
        $this->actingAs($lain)->delete(route('sistem.identitas.berkas.destroy', 'logo'))->assertForbidden();
    }

    $this->actingAs($this->admin)->get(route('sistem.identitas.edit'))->assertOk()
        ->assertSee(route('sistem.identitas.edit'));
    expect(Pengaturan::nilai('identitas.nama'))->toBeNull();
});

test('the saved name, short name and description show on the landing, login and sidebar', function () {
    simpanIdentitas([
        'nama_singkat' => 'SIMLITABMAS',
        'deskripsi' => "Baris pertama\nBaris kedua",
    ])->assertRedirect(route('sistem.identitas.edit'));

    expect(config('app.name'))->toBe('SIMLITABMAS Poltekom');

    $this->get('/')->assertOk()
        ->assertSee('<title>SIMLITABMAS Poltekom</title>', false)
        ->assertSee("Baris pertama\nBaris kedua");

    $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()
        ->assertSee('SIMLITABMAS</span>', false);

    auth()->logout();
    $this->get(route('login'))->assertOk()->assertSee('SIMLITABMAS Poltekom');
});

test('empty fields fall back to the defaults', function () {
    simpanIdentitas(['nama_singkat' => '', 'deskripsi' => '']);

    $this->get('/')->assertOk()
        ->assertSee('Sistem informasi penelitian dan pengabdian')
        ->assertSee(asset('favicon.ico'))
        ->assertDontSee(route('identitas.berkas', 'logo'));
    // Without a short name the sidebar shows the full name.
    $this->actingAs($this->admin)->get(route('dashboard'))->assertSee('SIMLITABMAS Poltekom</span>', false);
});

test('uploaded images are served publicly and replace the old file', function () {
    simpanIdentitas([
        'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
        'latar' => UploadedFile::fake()->image('latar.jpg', 1920, 1080),
    ])->assertSessionHasNoErrors();

    $logoLama = Pengaturan::nilai('identitas.logo');
    Storage::assertExists($logoLama);

    auth()->logout();
    $this->get('/')->assertOk()
        ->assertSee(route('identitas.berkas', 'favicon'))
        ->assertSee(route('identitas.berkas', 'latar'))
        // No dark variant yet, so the landing uses the main logo.
        ->assertSee(route('identitas.berkas', 'logo'));
    $this->get(route('identitas.berkas', 'logo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get(route('identitas.berkas', 'logo-gelap'))->assertNotFound();

    simpanIdentitas(['logo' => UploadedFile::fake()->image('baru.png', 200, 200)]);
    Storage::assertMissing($logoLama);
    Storage::assertExists(Pengaturan::nilai('identitas.logo'));
});

test('the sidebar uses the dark-background logo when there is one', function () {
    simpanIdentitas([
        'logo' => UploadedFile::fake()->image('logo.png'),
        'logo_gelap' => UploadedFile::fake()->image('logo-gelap.png'),
    ]);

    $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()
        ->assertSee(route('identitas.berkas', 'logo-gelap'))
        ->assertDontSee(route('identitas.berkas', 'logo').'?', false);
});

test('an uploaded image can be put back to the default', function () {
    simpanIdentitas(['logo' => UploadedFile::fake()->image('logo.png')]);
    $logo = Pengaturan::nilai('identitas.logo');

    $this->actingAs($this->admin)->delete(route('sistem.identitas.berkas.destroy', 'logo'))
        ->assertRedirect(route('sistem.identitas.edit'));

    Storage::assertMissing($logo);
    expect(Pengaturan::nilai('identitas.logo'))->toBeNull();
    $this->get(route('identitas.berkas', 'logo'))->assertNotFound();
});

test('uploads are checked for type and size', function () {
    simpanIdentitas([
        'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        'favicon' => UploadedFile::fake()->image('favicon.png')->size(600),
        'latar' => UploadedFile::fake()->image('latar.jpg')->size(4000),
        'nama_singkat' => str_repeat('A', 21),
    ])->assertSessionHasErrors(['logo', 'favicon', 'latar', 'nama_singkat']);

    $this->actingAs($this->admin)->put(route('sistem.identitas.update'), ['nama' => ''])->assertSessionHasErrors('nama');
    expect(Pengaturan::nilai('identitas.logo'))->toBeNull();
});

test('the desktop sidebar can be collapsed', function () {
    $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()
        ->assertSee('onclick="aturSidebar()"', false)
        ->assertSee('title="Identitas Aplikasi"', false);
});
