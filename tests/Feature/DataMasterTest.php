<?php

use App\Enums\JabatanFungsional;
use App\Enums\JenisKegiatan;
use App\Models\BidangFokus;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\PeringkatJurnal;
use App\Models\ProgramStudi;
use App\Models\Publikasi;
use App\Models\Role;
use App\Models\RumpunIlmu;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function akunPeran(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

beforeEach(function () {
    $this->admin = akunPeran(Role::ADMIN_SISTEM);
});

test('only the system admin manages reference data', function () {
    foreach (['sistem.fakultas.index', 'sistem.rumpun-ilmu.index', 'sistem.bidang-fokus.index', 'sistem.peringkat-jurnal.index'] as $rute) {
        $this->actingAs(akunPeran(Role::ADMIN_LPPM))->get(route($rute))->assertForbidden();
        $this->actingAs(akunPeran(Role::DOSEN))->get(route($rute))->assertForbidden();
        $this->actingAs($this->admin)->get(route($rute))->assertOk();
    }
    $this->actingAs(akunPeran(Role::ADMIN_LPPM))->get(route('sistem.impor.create', 'prodi'))->assertForbidden();
});

test('the sidebar groups the system admin reference data', function () {
    $this->actingAs($this->admin)->get(route('sistem.rumpun-ilmu.index'))->assertOk()
        ->assertSee('Data Master')
        ->assertSee(route('sistem.fakultas.index'))
        ->assertSee(route('sistem.peringkat-jurnal.index'));
});

test('the LPPM admin sidebar is grouped by stage of the grant', function () {
    $this->actingAs(akunPeran(Role::ADMIN_LPPM))->get(route('admin.dashboard'))->assertOk()
        ->assertSeeInOrder(['Persiapan Hibah', 'Seleksi', 'Pelaksanaan', 'Laporan &amp; Luaran', 'Pengaturan LPPM'], false)
        ->assertSee(route('kelola.pengumuman.index'))
        ->assertDontSee(route('sistem.fakultas.index'));
});

test('the system admin adds and edits faculties and programmes', function () {
    $this->actingAs($this->admin)->post(route('sistem.fakultas.store'), ['kode' => 'FT', 'nama' => 'Fakultas Teknik', 'is_active' => '1'])
        ->assertRedirect(route('sistem.fakultas.index'));
    $ft = Fakultas::firstWhere('kode', 'FT');

    $this->actingAs($this->admin)->post(route('sistem.prodi.store'), [
        'fakultas_id' => $ft->id, 'kode' => 'TI', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1', 'is_active' => '1',
    ])->assertRedirect(route('sistem.fakultas.index'));
    $ti = ProgramStudi::firstWhere('kode', 'TI');

    $this->actingAs($this->admin)->put(route('sistem.prodi.update', $ti), [
        'fakultas_id' => $ft->id, 'kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1', 'is_active' => '0',
    ])->assertRedirect();

    expect($ti->fresh())->nama->toBe('Informatika')->is_active->toBeFalse();
    $this->actingAs($this->admin)->get(route('sistem.fakultas.index'))->assertSee('Informatika')->assertSee('Nonaktif');

    $this->actingAs($this->admin)->post(route('sistem.prodi.store'), [
        'fakultas_id' => $ft->id, 'kode' => 'TI', 'nama' => 'Ganda', 'jenjang' => 'S9',
    ])->assertSessionHasErrors(['kode', 'jenjang']);
});

test('an inactive programme leaves the profile dropdown but stays for lecturers already in it', function () {
    $ft = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik']);
    $lama = $ft->programStudi()->create(['kode' => 'TL', 'nama' => 'Teknik Lama', 'jenjang' => 'S1', 'is_active' => false]);
    $baru = $ft->programStudi()->create(['kode' => 'TB', 'nama' => 'Teknik Baru', 'jenjang' => 'S1']);

    $pindah = akunPeran(Role::DOSEN);
    $this->actingAs($pindah)->get(route('profil-dosen.edit'))->assertSee('Teknik Baru')->assertDontSee('Teknik Lama');
    $this->actingAs($pindah)->put(route('profil-dosen.update'), ['nama' => 'Ani', 'prodi_id' => $lama->id])
        ->assertSessionHasErrors('prodi_id');

    $tetap = Dosen::factory()->create(['prodi_id' => $lama->id]);
    $this->actingAs($tetap->user)->get(route('profil-dosen.edit'))->assertSee('Teknik Lama');
    $this->actingAs($tetap->user)->put(route('profil-dosen.update'), ['nama' => 'Budi', 'prodi_id' => $lama->id])
        ->assertSessionHasNoErrors();
});

test('the lecturer picks the academic rank from the national list', function () {
    $user = akunPeran(Role::DOSEN);

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertSee('Lektor Kepala')->assertSee('Tenaga Pengajar');
    $this->actingAs($user)->put(route('profil-dosen.update'), ['nama' => 'Ani', 'jabatan_fungsional' => 'lektor_kepala'])
        ->assertSessionHasNoErrors();
    expect($user->fresh()->dosen->jabatan_fungsional)->toBe(JabatanFungsional::LektorKepala);

    $this->actingAs($user)->put(route('profil-dosen.update'), ['nama' => 'Ani', 'jabatan_fungsional' => 'Dekan'])
        ->assertSessionHasErrors('jabatan_fungsional');
});

test('the science classification keeps each level under the one above', function () {
    $this->actingAs($this->admin)->post(route('sistem.rumpun-ilmu.store'), ['tingkat' => 'rumpun', 'kode' => '400', 'nama' => 'Ilmu Teknik', 'is_active' => '1'])
        ->assertSessionHasNoErrors();
    $rumpun = RumpunIlmu::firstWhere('kode', '400');

    $this->actingAs($this->admin)->post(route('sistem.rumpun-ilmu.store'), ['tingkat' => 'cabang', 'kode' => '451', 'nama' => 'Telekomunikasi', 'induk_id' => $rumpun->id])
        ->assertSessionHasErrors('induk_id');

    $this->actingAs($this->admin)->post(route('sistem.rumpun-ilmu.store'), ['tingkat' => 'pohon', 'kode' => '450', 'nama' => 'Teknik Elektro', 'induk_id' => $rumpun->id, 'is_active' => '1'])
        ->assertSessionHasNoErrors();
    $pohon = RumpunIlmu::firstWhere('kode', '450');
    expect($pohon->induk_id)->toBe($rumpun->id);

    $this->actingAs($this->admin)->put(route('sistem.rumpun-ilmu.update', $rumpun), ['tingkat' => 'pohon', 'kode' => '400', 'nama' => 'Ilmu Teknik', 'induk_id' => $pohon->id])
        ->assertSessionHasErrors();

    $this->actingAs($this->admin)->get(route('sistem.rumpun-ilmu.index'))->assertSeeInOrder(['Ilmu Teknik', 'Teknik Elektro']);
});

test('focus areas nest one level within the same kind of activity', function () {
    $utama = BidangFokus::create(['jenis_kegiatan' => JenisKegiatan::Penelitian, 'kode' => 'RIP-01', 'nama' => 'TIK']);

    $this->actingAs($this->admin)->post(route('sistem.bidang-fokus.store'), [
        'jenis_kegiatan' => 'pkm', 'kode' => 'PKM-09', 'nama' => 'UMKM digital', 'induk_id' => $utama->id,
    ])->assertSessionHasErrors('induk_id');

    $this->actingAs($this->admin)->post(route('sistem.bidang-fokus.store'), [
        'jenis_kegiatan' => 'penelitian', 'kode' => 'RIP-01-1', 'nama' => 'Kecerdasan buatan', 'induk_id' => $utama->id, 'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect(BidangFokus::firstWhere('kode', 'RIP-01-1')->induk_id)->toBe($utama->id);
});

test('journal ranks come from reference data and keep their code once used', function () {
    expect(PeringkatJurnal::where('kode', 'sinta_2')->value('nama'))->toBe('SINTA 2');

    $this->actingAs($this->admin)->post(route('sistem.peringkat-jurnal.store'), [
        'kode' => 'int_bereputasi', 'nama' => 'Jurnal internasional bereputasi', 'urutan' => 5, 'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $publikasi = Publikasi::factory()->create(['peringkat' => 'sinta_2']);
    $sinta2 = PeringkatJurnal::firstWhere('kode', 'sinta_2');
    $this->actingAs($this->admin)->put(route('sistem.peringkat-jurnal.update', $sinta2), [
        'kode' => 'sinta_dua', 'nama' => 'SINTA 2 (S2)', 'urutan' => 60, 'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect($sinta2->fresh())->kode->toBe('sinta_2')->nama->toBe('SINTA 2 (S2)')
        ->and($publikasi->fresh()->namaPeringkat())->toBe('SINTA 2 (S2)');
});

test('the publication form offers only active ranks', function () {
    PeringkatJurnal::where('kode', 'sinta_6')->update(['is_active' => false]);
    $dosen = Dosen::factory()->create();

    $this->actingAs($dosen->user)->get(route('karya.publikasi.create'))->assertOk()
        ->assertSee('SINTA 5')->assertDontSee('SINTA 6');
});

test('programmes are imported from CSV after a preview', function () {
    Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik'])
        ->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);

    $csv = "\u{FEFF}kode_fakultas;nama_fakultas;kode_prodi;nama_prodi;jenjang\nFT;Fakultas Teknik;TI;Teknik Informatika;S1\nFEB;Fakultas Ekonomi;MJ;Manajemen;S1\n";

    $this->actingAs($this->admin)->post(route('sistem.impor.periksa', 'prodi'), ['berkas' => UploadedFile::fake()->createWithContent('prodi.csv', $csv)])
        ->assertOk()->assertSee('1 baru, 1 diperbarui')->assertSee('Simpan 2 baris');
    expect(ProgramStudi::count())->toBe(1);

    $this->actingAs($this->admin)->post(route('sistem.impor.simpan', 'prodi'))->assertRedirect(route('sistem.fakultas.index'));

    expect(ProgramStudi::firstWhere('kode', 'TI')->nama)->toBe('Teknik Informatika')
        ->and(ProgramStudi::firstWhere('kode', 'MJ')->fakultas->kode)->toBe('FEB');
});

test('a CSV with errors is shown but cannot be saved', function () {
    $csv = "kode,nama,tingkat,kode_induk\n400,Ilmu Teknik,rumpun,\n458,Teknik Informatika,cabang,400\n";

    $this->actingAs($this->admin)->post(route('sistem.impor.periksa', 'rumpun-ilmu'), ['berkas' => UploadedFile::fake()->createWithContent('rumpun.csv', $csv)])
        ->assertOk()->assertSee('1 baris bergalat')->assertDontSee('Simpan 2 baris');

    $this->actingAs($this->admin)->post(route('sistem.impor.simpan', 'rumpun-ilmu'))->assertSessionHasErrors('berkas');
    expect(RumpunIlmu::count())->toBe(0);
});

test('the science classification is imported with parents from earlier rows', function () {
    $csv = "kode,nama,tingkat,kode_induk\n400,Ilmu Teknik,rumpun,\n450,Teknik Elektro,pohon,400\n458,Teknik Informatika,cabang,450\n";

    $this->actingAs($this->admin)->post(route('sistem.impor.periksa', 'rumpun-ilmu'), ['berkas' => UploadedFile::fake()->createWithContent('rumpun.csv', $csv)])
        ->assertOk()->assertSee('3 baru');
    $this->actingAs($this->admin)->post(route('sistem.impor.simpan', 'rumpun-ilmu'))->assertRedirect(route('sistem.rumpun-ilmu.index'));

    expect(RumpunIlmu::firstWhere('kode', '458')->jalur())->toBe('Ilmu Teknik › Teknik Elektro › Teknik Informatika');
});

test('a CSV missing template columns is refused', function () {
    $this->actingAs($this->admin)->post(route('sistem.impor.periksa', 'prodi'), ['berkas' => UploadedFile::fake()->createWithContent('x.csv', "kode,nama\nA,B\n")])
        ->assertSessionHasErrors('berkas');
});

test('the CSV template can be downloaded', function () {
    $isi = $this->actingAs($this->admin)->get(route('sistem.impor.templat', 'rumpun-ilmu'))->assertOk()->streamedContent();

    expect($isi)->toStartWith("kode,nama,tingkat,kode_induk\n");
});
