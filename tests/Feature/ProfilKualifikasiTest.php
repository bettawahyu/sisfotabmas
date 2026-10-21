<?php

use App\Models\Dosen;
use App\Models\Role;
use App\Models\User;
use App\Services\DataDosen;

function akunDosenBaru(): User
{
    $user = User::factory()->create(['name' => 'Nama Akun Lama']);
    $user->assignRole(Role::DOSEN);

    return $user;
}

test('a lecturer records certification, Pekerti, AA, ORCID, TMT and expertise', function () {
    $user = akunDosenBaru();

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertOk()
        ->assertSee($user->email)->assertSee('Sertifikasi dosen')->assertSee('Pekerti')->assertSee('Applied Approach')
        ->assertSee('TMT sebagai dosen')->assertSee('ORCID')->assertSee('Bidang keahlian');

    $this->actingAs($user)->put(route('profil-dosen.update'), [
        'nama' => 'Ani Lestari',
        'orcid' => 'https://orcid.org/0000-0002-1825-009x',
        'tmt_dosen' => '2015-03-01',
        'bidang_keahlian' => 'Kecerdasan buatan',
        'sertifikasi_dosen' => '1', 'no_serdos' => '17110100123', 'tahun_serdos' => '2018',
        'pekerti' => '1', 'tahun_pekerti' => '2016',
        'applied_approach' => '0', 'tahun_applied_approach' => '2017',
    ])->assertSessionHasNoErrors()->assertRedirect(route('profil-dosen.edit'));

    $dosen = $user->fresh()->dosen;
    expect($dosen->orcid)->toBe('0000-0002-1825-009X')
        ->and($dosen->tmt_dosen->format('Y-m-d'))->toBe('2015-03-01')
        ->and($dosen->bidang_keahlian)->toBe('Kecerdasan buatan')
        ->and($dosen->sertifikasi_dosen)->toBeTrue()
        ->and($dosen->no_serdos)->toBe('17110100123')
        ->and($dosen->tahun_serdos)->toBe(2018)
        ->and($dosen->pekerti)->toBeTrue()
        ->and($dosen->tahun_pekerti)->toBe(2016)
        // "Belum" drops the year that came with it.
        ->and($dosen->applied_approach)->toBeFalse()
        ->and($dosen->tahun_applied_approach)->toBeNull();
});

test('a certified lecturer must give the certificate number and year, and ORCID is checked', function () {
    $this->actingAs(akunDosenBaru())->put(route('profil-dosen.update'), [
        'nama' => 'Ani', 'sertifikasi_dosen' => '1', 'orcid' => '1234',
    ])->assertSessionHasErrors(['no_serdos', 'tahun_serdos', 'orcid']);
});

test('the account name follows the lecturer name', function () {
    $user = akunDosenBaru();
    $this->actingAs($user)->put(route('profil-dosen.update'), ['nama' => 'Ani Lestari'])->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('Ani Lestari');
    $this->actingAs($user->fresh())->get(route('profile.edit'))->assertOk()->assertSee('Profil Dosen');

    // A rename made elsewhere, such as by the system admin, reaches the lecturer too.
    $user->fresh()->update(['name' => 'Ani L.']);
    expect($user->fresh()->dosen->nama)->toBe('Ani L.');
});

test('the lecturer profile also keeps address, phone, birth, marital status and sex', function () {
    $user = akunDosenBaru();

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertOk()
        ->assertSee('Profil Dosen')->assertSee('Alamat')->assertSee('Status pernikahan')->assertSee('Jenis kelamin');

    $this->actingAs($user)->put(route('profil-dosen.update'), [
        'nama' => 'Budi Santoso',
        'alamat' => 'Jl. Kaliurang 5, Sleman', 'no_hp' => '+62 812-3456-7890',
        'tempat_lahir' => 'Yogyakarta', 'tgl_lahir' => '1990-05-17',
        'status_pernikahan' => 'menikah', 'jenis_kelamin' => 'L',
    ])->assertSessionHasNoErrors()->assertRedirect(route('profil-dosen.edit'));

    // Kept on the lecturer, which every transaction reads; the account only takes the name.
    $dosen = $user->fresh()->dosen;
    expect($user->fresh()->name)->toBe('Budi Santoso')
        ->and($dosen->alamat)->toBe('Jl. Kaliurang 5, Sleman')
        ->and($dosen->no_hp)->toBe('+62 812-3456-7890')
        ->and($dosen->tempat_lahir)->toBe('Yogyakarta')
        ->and($dosen->tgl_lahir->format('Y-m-d'))->toBe('1990-05-17')
        ->and($dosen->status_pernikahan)->toBe('menikah')
        ->and($dosen->jenis_kelamin)->toBe('L');

    $this->actingAs($user)->put(route('profil-dosen.update'), [
        'nama' => 'Budi', 'status_pernikahan' => 'janda', 'no_hp' => 'abc',
    ])->assertSessionHasErrors(['status_pernikahan', 'no_hp']);
});

test('Data Dosen and its Excel carry the new profile and qualification fields', function () {
    $user = akunDosenBaru();
    Dosen::factory()->for($user)->create(['nama' => 'Citra', 'no_hp' => '08123', 'tempat_lahir' => 'Solo', 'tgl_lahir' => '1985-01-02',
        'status_pernikahan' => 'menikah', 'jenis_kelamin' => 'P', 'alamat' => 'Jl. Merapi 1', 'sertifikasi_dosen' => true, 'no_serdos' => 'SD-1', 'tahun_serdos' => 2019,
        'pekerti' => true, 'tahun_pekerti' => 2014, 'tmt_dosen' => '2012-09-01']);
    $operator = User::factory()->create();
    $operator->assignRole(Role::OPERATOR);

    $this->actingAs($operator)->get(route('data-dosen.index'))->assertOk()
        ->assertSee('Jl. Merapi 1')->assertSee('Sudah 2019')->assertSee('Menikah')->assertSee('Perempuan');

    $data = app(DataDosen::class);
    $baris = $data->sheetLengkap($data->query($operator)->get())->firstWhere('Nama lengkap', 'Citra');
    expect($baris['No. telepon'])->toBe('08123')
        ->and($baris['Tanggal lahir'])->toBe('1985-01-02')
        ->and($baris['Status pernikahan'])->toBe('Menikah')
        ->and($baris['Sertifikasi dosen'])->toBe('Sudah')
        ->and($baris['No. sertifikat pendidik'])->toBe('SD-1')
        ->and($baris['Pekerti'])->toBe('Sudah')
        ->and($baris['Applied Approach (AA)'])->toBe('Belum')
        ->and($baris['TMT dosen'])->toBe('2012-09-01');
});

test('the lecturer profile carries the photo upload; the account page keeps only email and password', function () {
    $user = akunDosenBaru();

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertSee(route('profile.foto.update'), false);
    $this->actingAs($user)->get(route('profile.edit'))->assertDontSee(route('profile.foto.update'), false)
        ->assertSee($user->email)->assertSee('update_password_current_password', false);

    // Staff without a lecturer record still change their photo on the account page.
    $operator = User::factory()->create();
    $operator->assignRole(Role::OPERATOR);
    $this->actingAs($operator)->get(route('profile.edit'))->assertSee(route('profile.foto.update'), false);
});
