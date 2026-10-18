<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Tanda;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

beforeEach(function () {
    $this->sistem = User::factory()->create();
    $this->sistem->assignRole(Role::ADMIN_SISTEM);
});

/**
 * @param  list<list<string>>  $baris
 */
function berkasPengguna(array $baris, array $kepala = ['nama', 'email', 'peran', 'kata_sandi']): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'impor').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues($kepala));
    foreach ($baris as $b) {
        $writer->addRow(Row::fromValues($b));
    }
    $writer->close();

    return new UploadedFile($path, 'pengguna.xlsx', null, null, true);
}

test('the system admin can close and reopen self-registration', function () {
    $this->get('/register')->assertOk();
    $this->get('/')->assertSee(route('register'));

    $this->actingAs($this->sistem)->put(route('sistem.identitas.update'), ['nama' => 'Sisfotabmas', 'pendaftaran' => '0'])
        ->assertSessionHasNoErrors();
    $this->post('/logout');

    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Tamu', 'email' => 'tamu@example.com', 'password' => 'rahasia-panjang', 'password_confirmation' => 'rahasia-panjang',
    ])->assertNotFound();
    $this->get('/')->assertDontSee(route('register'));
    expect(User::where('email', 'tamu@example.com')->exists())->toBeFalse();

    // Saving the page again without the field leaves the choice alone.
    $this->actingAs($this->sistem)->put(route('sistem.identitas.update'), ['nama' => 'Sisfotabmas']);
    $this->post('/logout');
    $this->get('/register')->assertNotFound();

    $this->actingAs($this->sistem)->put(route('sistem.identitas.update'), ['nama' => 'Sisfotabmas', 'pendaftaran' => '1']);
    $this->post('/logout');
    $this->get('/register')->assertOk();
});

test('pages carry the description, keywords and author mark', function () {
    $this->get('/')->assertSee('<meta name="author" content="'.e(Tanda::penulis()).'">', false)
        ->assertSee('name="keywords"', false)
        ->assertSee('name="description"', false);
    $this->actingAs($this->sistem)->get(route('sistem.pengguna.index'))->assertSee(Tanda::penulis())->assertSee(Tanda::tautan());
});

test('the user template is an Excel file with locked help sheets', function () {
    $respons = $this->actingAs($this->sistem)->get(route('sistem.impor.templat', 'pengguna'))->assertOk();
    expect($respons->headers->get('content-disposition'))->toContain('templat-pengguna.xlsx');

    $zip = new ZipArchive;
    $zip->open($respons->baseResponse->getFile()->getPathname());
    $buku = $zip->getFromName('xl/workbook.xml');
    $lembarIsian = $zip->getFromName('xl/worksheets/sheet1.xml');
    $lembarPetunjuk = $zip->getFromName('xl/worksheets/sheet2.xml');
    $zip->close();

    expect($buku)->toContain('workbookProtection')->toContain('Pengguna')->toContain('Petunjuk')
        ->and($lembarPetunjuk)->toContain('<sheetProtection')
        ->and($lembarIsian)->not->toContain('<sheetProtection');
});

test('users are imported from Excel and must replace their first password', function () {
    User::factory()->create(['email' => 'lama@example.com']);

    $this->actingAs($this->sistem)->post(route('sistem.impor.periksa', 'pengguna'), ['berkas' => berkasPengguna([
        ['', 'kosong@example.com', 'dosen', 'rahasia-awal'],
        ['Lama', 'lama@example.com', 'dosen', 'rahasia-awal'],
        ['Salah', 'bukan-email', 'dosen', 'rahasia-awal'],
        ['Peran asing', 'asing@example.com', 'dosen, kepala_dunia', 'rahasia-awal'],
        ['Pendek', 'pendek@example.com', 'dosen', 'abc'],
    ])])->assertOk()->assertSee('Nama kosong.')->assertSee('Email sudah terdaftar.')->assertSee('Email tidak valid.')
        ->assertSee('kepala_dunia')->assertSee('Kata sandi awal minimal 8 karakter.')->assertDontSee('rahasia-awal');
    $this->actingAs($this->sistem)->post(route('sistem.impor.simpan', 'pengguna'))->assertSessionHasErrors('berkas');

    $this->actingAs($this->sistem)->post(route('sistem.impor.periksa', 'pengguna'), ['berkas' => berkasPengguna([
        ['Budi Santoso', 'Budi@Kampus.ac.id', 'dosen', 'Sandi-Budi-01'],
        ['Siti Aminah', 'siti@kampus.ac.id', 'Dosen; reviewer', 'Sandi-Siti-02'],
    ])])->assertOk()->assertSee('Simpan 2 baris')->assertDontSee('Sandi-Budi-01');
    expect(json_encode(session()->all()))->not->toContain('Sandi-Budi-01');

    $this->actingAs($this->sistem)->post(route('sistem.impor.simpan', 'pengguna'))
        ->assertRedirect(route('sistem.pengguna.index'));

    $budi = User::where('email', 'budi@kampus.ac.id')->sole();
    $siti = User::where('email', 'siti@kampus.ac.id')->sole();
    expect($budi->hasVerifiedEmail())->toBeTrue()
        ->and($budi->wajib_ganti_password)->toBeTrue()
        ->and(Hash::check('Sandi-Budi-01', $budi->password))->toBeTrue()
        ->and($budi->hasRole(Role::DOSEN))->toBeTrue()
        ->and($siti->hasRole(Role::DOSEN))->toBeTrue()
        ->and($siti->hasRole(Role::REVIEWER))->toBeTrue();

    $this->actingAs($budi)->get(route('dashboard'))->assertRedirect(route('password.wajib'));
});

test('an Excel file with changed headers or a non-Excel file is refused', function () {
    $this->actingAs($this->sistem)->post(route('sistem.impor.periksa', 'pengguna'), [
        'berkas' => berkasPengguna([['Budi', 'budi@kampus.ac.id', 'dosen', 'rahasia-awal']], ['nama lengkap', 'email', 'peran', 'kata_sandi']),
    ])->assertSessionHasErrors('berkas');

    $this->actingAs($this->sistem)->post(route('sistem.impor.periksa', 'pengguna'), [
        'berkas' => UploadedFile::fake()->createWithContent('pengguna.xlsx', "nama,email,peran,kata_sandi\nBudi,budi@kampus.ac.id,dosen,rahasia-awal\n"),
    ])->assertSessionHasErrors('berkas');
});
