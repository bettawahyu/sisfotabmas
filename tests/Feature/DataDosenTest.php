<?php

use App\Enums\JabatanFungsional;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Publikasi;
use App\Models\Role;
use App\Models\User;
use App\Services\DataDosen;

/**
 * A user with the given role; a unit head is tied to "prodi:<id>" or "fakultas:<id>".
 */
function pelihatDosen(string $peran, ?string $unit = null): User
{
    $user = User::factory()->create();
    $user->assignRole($peran);
    if ($unit !== null) {
        [$jenis, $id] = explode(':', $unit);
        $user->roles()->updateExistingPivot(Role::where('kode', $peran)->value('id'), ["{$jenis}_id" => (int) $id]);
    }

    return $user->fresh();
}

/**
 * The rows of every sheet in a downloaded workbook, keyed by sheet name.
 *
 * @return array<string, array<int, array<int, string>>>
 */
function bacaExcel(string $isi): array
{
    $berkas = tempnam(sys_get_temp_dir(), 'dosen');
    file_put_contents($berkas, $isi);
    $zip = new ZipArchive;
    $zip->open($berkas);
    preg_match_all('/<sheet name="([^"]+)"/', $zip->getFromName('xl/workbook.xml'), $nama);
    $teks = $zip->getFromName('xl/sharedStrings.xml') ?: '';
    $lembar = [];
    foreach ($nama[1] as $i => $n) {
        $lembar[html_entity_decode($n)] = $zip->getFromName('xl/worksheets/sheet'.($i + 1).'.xml');
    }
    $zip->close();
    unlink($berkas);

    return ['sheet' => array_keys($lembar), 'isi' => implode('', $lembar).$teks];
}

beforeEach(function () {
    $teknik = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik']);
    $this->teknik = $teknik;
    $this->informatika = $teknik->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $this->elektro = $teknik->programStudi()->create(['kode' => 'TE', 'nama' => 'Elektro', 'jenjang' => 'S1']);
    $ekonomi = Fakultas::create(['kode' => 'FE', 'nama' => 'Fakultas Ekonomi']);
    $this->manajemen = $ekonomi->programStudi()->create(['kode' => 'MN', 'nama' => 'Manajemen', 'jenjang' => 'S1']);

    $this->ani = Dosen::factory()->create(['prodi_id' => $this->informatika->id, 'nama' => 'Ani', 'gelar_belakang' => 'M.Kom.',
        'jabatan_fungsional' => JabatanFungsional::Lektor, 'pendidikan_terakhir' => 'S2', 'bidang_keahlian' => 'Kecerdasan buatan']);
    $this->ani->riwayatPendidikan()->createMany([
        ['jenjang' => 'S1', 'institusi' => 'Universitas Gadjah Mada', 'program_studi' => 'Ilmu Komputer', 'tahun_lulus' => 2010],
        ['jenjang' => 'S2', 'institusi' => 'Institut Teknologi Bandung', 'program_studi' => 'Informatika', 'tahun_lulus' => 2014],
    ]);
    Publikasi::factory()->olehDosen($this->ani)->create(['judul' => 'Jaringan saraf untuk padi', 'tahun' => 2025]);

    $this->budi = Dosen::factory()->create(['prodi_id' => $this->elektro->id, 'nama' => 'Budi', 'jabatan_fungsional' => JabatanFungsional::AsistenAhli]);
    $this->citra = Dosen::factory()->create(['prodi_id' => $this->manajemen->id, 'nama' => 'Citra']);
});

test('operators, LPPM admins and unit heads open the page from the menu', function (string $peran) {
    $user = pelihatDosen($peran);

    $this->actingAs($user)->get(route('dashboard'))->assertSee(route('data-dosen.index'), false);
    $this->actingAs($user)->get(route('data-dosen.index'))->assertOk()
        ->assertSee('Unduh sesuai filter')->assertSee('Unduh semua');
})->with([Role::OPERATOR, Role::ADMIN_LPPM, Role::PIMPINAN_UNIT]);

test('other roles cannot see lecturers\' data', function (string $peran) {
    $user = pelihatDosen($peran);

    $this->actingAs($user)->get(route('dashboard'))->assertDontSee(route('data-dosen.index'), false);
    $this->actingAs($user)->get(route('data-dosen.index'))->assertForbidden();
    $this->actingAs($user)->get(route('data-dosen.unduh'))->assertForbidden();
    $this->actingAs($user)->get(route('data-dosen.unduh-semua'))->assertForbidden();
})->with([Role::DOSEN, Role::REVIEWER, Role::KEUANGAN, Role::PIMPINAN]);

test('operators and LPPM see every lecturer, with the full qualifications in a dialog', function () {
    $this->actingAs(pelihatDosen(Role::OPERATOR))->get(route('data-dosen.index'))->assertOk()
        ->assertSee('Ani, M.Kom.')->assertSee('Budi')->assertSee('Citra')
        ->assertSee('dosen-'.$this->ani->id)
        ->assertSee('Kecerdasan buatan')->assertSee('Institut Teknologi Bandung')->assertSee('Lektor');
});

test('a kaprodi sees only their programme and a dean their faculty', function () {
    $kaprodi = pelihatDosen(Role::PIMPINAN_UNIT, 'prodi:'.$this->informatika->id);
    $this->actingAs($kaprodi)->get(route('data-dosen.index'))->assertOk()
        ->assertSee('Ani')->assertDontSee('Budi')->assertDontSee('Citra')->assertDontSee('Elektro');

    // Asking for another programme does not widen the view.
    $this->actingAs($kaprodi)->get(route('data-dosen.index', ['prodi_id' => $this->elektro->id]))->assertDontSee('Budi');

    $dekan = pelihatDosen(Role::PIMPINAN_UNIT, 'fakultas:'.$this->teknik->id);
    $this->actingAs($dekan)->get(route('data-dosen.index'))
        ->assertSee('Ani')->assertSee('Budi')->assertDontSee('Citra');

    // A kaprodi who also runs LPPM sees everyone.
    $kaprodi->assignRole(Role::ADMIN_LPPM);
    $this->actingAs($kaprodi->fresh())->get(route('data-dosen.index'))->assertSee('Citra');
});

test('the list is narrowed by name, programme, rank and degree', function () {
    $data = app(DataDosen::class);
    $operator = pelihatDosen(Role::OPERATOR);

    expect($data->query($operator, ['cari' => 'bud'])->pluck('nama')->all())->toBe(['Budi'])
        ->and($data->query($operator, ['cari' => $this->ani->nidn])->pluck('nama')->all())->toBe(['Ani'])
        ->and($data->query($operator, ['prodi_id' => $this->manajemen->id])->pluck('nama')->all())->toBe(['Citra'])
        ->and($data->query($operator, ['jabatan_fungsional' => 'asisten_ahli'])->pluck('nama')->all())->toBe(['Budi'])
        ->and($data->query($operator, ['pendidikan_terakhir' => 'S2'])->pluck('nama')->all())->toBe(['Ani']);

    $this->actingAs($operator)->get(route('data-dosen.index', ['jabatan_fungsional' => 'rektor']))->assertSessionHasErrors('jabatan_fungsional');
});

test('the filtered download has Dosen, Riwayat Pendidikan and Publikasi sheets', function () {
    $data = app(DataDosen::class);
    $sheet = $data->sheetTersaring($data->query(pelihatDosen(Role::ADMIN_LPPM), ['prodi_id' => $this->informatika->id])->get());

    expect(array_keys($sheet))->toBe(['Dosen', 'Riwayat Pendidikan', 'Publikasi'])
        ->and($sheet['Dosen'])->toHaveCount(1)
        ->and($sheet['Dosen'][0]['Nama lengkap'])->toBe('Ani, M.Kom.')
        ->and($sheet['Dosen'][0]['Program studi'])->toBe('S1 Informatika')
        ->and($sheet['Dosen'][0]['Jabatan fungsional'])->toBe('Lektor')
        ->and($sheet['Dosen'][0]['Jumlah publikasi'])->toBe(1)
        ->and($sheet['Riwayat Pendidikan']->pluck('Institusi')->all())->toBe(['Universitas Gadjah Mada', 'Institut Teknologi Bandung'])
        ->and($sheet['Publikasi']->pluck('Judul')->all())->toBe(['Jaringan saraf untuk padi']);

    $unduhan = $this->actingAs(pelihatDosen(Role::OPERATOR))->get(route('data-dosen.unduh', ['prodi_id' => $this->informatika->id]));
    $unduhan->assertOk();
    expect($unduhan->headers->get('content-disposition'))->toContain('data-dosen-');
    $excel = bacaExcel($unduhan->streamedContent());
    expect($excel['sheet'])->toBe(['Dosen', 'Riwayat Pendidikan', 'Publikasi'])
        ->and($excel['isi'])->toContain('Jaringan saraf untuk padi')->not->toContain('Budi');
});

test('the full download is one sheet with each degree in its own columns', function () {
    $data = app(DataDosen::class);
    $baris = $data->sheetLengkap($data->query(pelihatDosen(Role::OPERATOR))->get());

    expect($baris->pluck('Nama lengkap')->all())->toBe(['Ani, M.Kom.', 'Budi', 'Citra'])
        ->and($baris[0]['S1 institusi'])->toBe('Universitas Gadjah Mada')
        ->and($baris[0]['S2 tahun lulus'])->toBe(2014)
        ->and($baris[0]['S3 institusi'])->toBeNull();

    $unduhan = $this->actingAs(pelihatDosen(Role::PIMPINAN_UNIT, 'prodi:'.$this->elektro->id))
        ->get(route('data-dosen.unduh-semua', ['prodi_id' => $this->informatika->id]));
    $unduhan->assertOk();
    expect($unduhan->headers->get('content-disposition'))->toContain('data-dosen-lengkap-');

    // A kaprodi's full download still holds only their programme.
    $excel = bacaExcel($unduhan->streamedContent());
    expect($excel['sheet'])->toHaveCount(1)
        ->and($excel['isi'])->toContain('Budi')->not->toContain('Ani')->not->toContain('Citra');
});
