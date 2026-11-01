<?php

use App\Enums\JenisKegiatan;
use App\Enums\StatusProposal;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Mitra;
use App\Models\RiwayatKegiatan;
use App\Models\Role;
use App\Services\Impor\ImporRiwayat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Every sheet of a downloaded workbook, as rows keyed by the header.
 *
 * @return array<string, list<array<string, mixed>>>
 */
function bacaUnduhan(TestResponse $respons): array
{
    $path = tempnam(sys_get_temp_dir(), 'unduh').'.xlsx';
    file_put_contents($path, $respons->streamedContent());
    $reader = new Reader;
    $reader->open($path);
    $hasil = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        $kepala = null;
        foreach ($sheet->getRowIterator() as $row) {
            $sel = $row->toArray();
            if ($kepala === null) {
                $kepala = $sel;

                continue;
            }
            $hasil[$sheet->getName()][] = array_combine($kepala, array_pad($sel, count($kepala), null));
        }
        $hasil[$sheet->getName()] ??= [];
    }
    $reader->close();

    return $hasil;
}

/**
 * An import file: the template's header and the given rows by column name.
 *
 * @param  list<array<string, string>>  $baris
 */
function berkasRiwayat(array $baris, JenisKegiatan $jenis = JenisKegiatan::Penelitian): UploadedFile
{
    $kolom = (new ImporRiwayat($jenis, pengguna(Role::OPERATOR)))->kolom();
    $path = tempnam(sys_get_temp_dir(), 'impor').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues($kolom));
    foreach ($baris as $b) {
        $writer->addRow(Row::fromValues(array_map(fn ($k) => $b[$k] ?? '', $kolom)));
    }
    $writer->close();

    return new UploadedFile($path, 'riwayat.xlsx', null, null, true);
}

beforeEach(function () {
    Storage::fake();
    $teknik = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik']);
    $this->informatika = $teknik->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $this->elektro = $teknik->programStudi()->create(['kode' => 'TE', 'nama' => 'Elektro', 'jenjang' => 'S1']);
    $this->ani = Dosen::factory()->create(['prodi_id' => $this->informatika->id, 'nama' => 'Ani Rahma', 'nidn' => '0012345678']);
    $this->budi = Dosen::factory()->create(['prodi_id' => $this->informatika->id, 'nama' => 'Budi Santoso', 'nidn' => '0023456789']);
    $this->citra = Dosen::factory()->create(['prodi_id' => $this->elektro->id, 'nama' => 'Citra Dewi', 'nidn' => '0034567890']);
});

test('the lecturer records download with the page filters and the final report status', function () {
    $tanpaLaporan = riwayatRekap([$this->ani, $this->budi], ['judul' => 'Pemetaan air sumur']);
    $denganLaporan = riwayatRekap([$this->citra], ['judul' => 'Sensor kelembapan tanah']);
    $denganLaporan->berkas()->create(['kategori' => RiwayatKegiatan::BERKAS_LAPORAN, 'nama_asli' => 'laporan.pdf', 'path' => 'x/laporan.pdf', 'mime' => 'application/pdf', 'ukuran_byte' => 10]);
    riwayatRekap([$this->citra], ['judul' => 'Masih menunggu'], RiwayatKegiatan::MENUNGGU);

    $admin = pengguna(Role::ADMIN_LPPM);
    $this->actingAs($admin)->get(route('riwayat-dosen.index', ['penelitian', 'status' => 'valid']))
        ->assertSee(route('riwayat-dosen.ekspor', ['penelitian', 'status' => 'valid']), false);
    $baris = bacaUnduhan($this->actingAs($admin)->get(route('riwayat-dosen.ekspor', ['penelitian', 'status' => 'valid']))->assertOk())['Sheet1'];

    expect(collect($baris)->pluck('Judul')->sort()->values()->all())->toBe(['Pemetaan air sumur', 'Sensor kelembapan tanah']);
    $pemetaan = collect($baris)->firstWhere('Judul', 'Pemetaan air sumur');
    expect($pemetaan)->toMatchArray(['NIDN ketua' => '0012345678', 'NIDN anggota' => '0023456789', 'Laporan akhir' => 'Belum unggah', 'Status verifikasi' => 'Valid'])
        ->and(collect($baris)->firstWhere('Judul', 'Sensor kelembapan tanah')['Laporan akhir'])->toBe('Sudah unggah');

    $kaprodi = kaprodiRekap($this->elektro->id);
    $milikUnit = bacaUnduhan($this->actingAs($kaprodi)->get(route('riwayat-dosen.ekspor', ['penelitian', 'status' => 'semua'])))['Sheet1'];
    expect(collect($milikUnit)->pluck('Judul')->sort()->values()->all())->toBe(['Masih menunggu', 'Sensor kelembapan tanah']);

    $this->actingAs($this->ani->user)->get(route('riwayat-dosen.ekspor', 'penelitian'))->assertForbidden();
    $this->actingAs($this->ani->user)->get(route('riwayat.index', 'penelitian'))->assertSee('Laporan akhir belum diunggah');
});

test('a lecturer downloads their own grants and records', function () {
    hibahRekap($this->ani, StatusProposal::Selesai)->update(['judul' => 'Hibah irigasi tetes']);
    riwayatRekap([$this->budi, $this->ani], ['judul' => 'Pemetaan air sumur', 'status_kegiatan' => RiwayatKegiatan::BERJALAN]);
    riwayatRekap([$this->citra], ['judul' => 'Milik orang lain']);

    $this->actingAs($this->ani->user)->get(route('riwayat.index', 'penelitian'))->assertSee(route('riwayat.ekspor', 'penelitian'));
    $baris = collect(bacaUnduhan($this->actingAs($this->ani->user)->get(route('riwayat.ekspor', 'penelitian'))->assertOk())['Sheet1']);

    expect($baris->pluck('Judul')->all())->toBe(['Hibah irigasi tetes', 'Pemetaan air sumur'])
        ->and($baris[0])->toMatchArray(['Sumber dana' => 'Hibah LPPM', 'Laporan akhir' => 'Sudah unggah', 'Status kegiatan' => 'Selesai'])
        ->and($baris[1])->toMatchArray(['Ketua' => $this->budi->namaLengkap(), 'Laporan akhir' => '-']);
});

test('the summary downloads as one sheet per table', function () {
    hibahRekap($this->ani, StatusProposal::Selesai, 2025, JenisKegiatan::Pkm);
    riwayatRekap([$this->budi], ['jenis_kegiatan' => JenisKegiatan::Pkm, 'kabupaten_kota_kode' => '3404']);

    $lembar = bacaUnduhan($this->actingAs(pengguna(Role::PIMPINAN))
        ->get(route('rekap-riwayat.ekspor', ['pkm', 'tahun_dari' => 2025, 'tahun_sampai' => 2025]))->assertOk());

    expect(array_keys($lembar))->toBe(['Per tahun', 'Per program studi', 'Per dosen', 'Sebaran lokasi'])
        ->and($lembar['Per tahun'][1])->toMatchArray(['Tahun' => 'Total', 'Hibah LPPM' => 1, 'Mandiri' => 1, 'Jumlah' => 2])
        ->and(collect($lembar['Per dosen'])->pluck('Dosen')->sort()->values()->all())->toBe(['Ani Rahma', 'Budi Santoso'])
        ->and($lembar['Sebaran lokasi'][0])->toMatchArray(['Kabupaten/kota' => 'Kabupaten Sleman', 'Kegiatan' => 1]);
    $this->actingAs($this->ani->user)->get(route('rekap-riwayat.ekspor', 'pkm'))->assertForbidden();
});

test('LPPM imports old records, validated at once and without their final report', function () {
    $operator = pengguna(Role::OPERATOR);
    $this->actingAs($operator)->get(route('riwayat-impor.create', 'pkm'))->assertOk()->assertSee('Unduh templat PkM');
    $templat = $this->actingAs($operator)->get(route('riwayat-impor.templat', 'pkm'))->assertOk();
    expect($templat->headers->get('content-disposition'))->toContain('templat-impor-pkm.xlsx');

    $baris = [
        'judul' => 'Pelatihan pemasaran daring', 'tahun_mulai' => '2023', 'status_kegiatan' => 'Selesai', 'sumber_dana' => 'mandiri',
        'nilai_dana' => 'Rp 2.500.000', 'nidn_ketua' => '12345678', 'nidn_anggota' => '0023456789',
        'mahasiswa' => 'Ayu Lestari (2201001); Bagus', 'mitra' => 'UMKM Keripik Salak; Kelompok Tani Makmur',
        'kabupaten_kota' => 'Sleman', 'alamat_lokasi' => 'Desa Wonokerto', 'jumlah_sasaran' => '25',
        'catatan_dampak' => 'Penjualan naik.', 'tautan_media' => 'https://koran.example/a https://koran.example/b',
    ];
    $tani = new Mitra(['nama' => 'Kelompok Tani Makmur', 'bidang' => 'Pertanian', 'is_active' => true]);
    $tani->forceFill(['status' => Mitra::MASTER])->save();

    $this->actingAs($operator)->post(route('riwayat-impor.periksa', 'pkm'), ['berkas' => berkasRiwayat([
        $baris,
        ['judul' => 'Tanpa ketua', 'tahun_mulai' => '2023', 'status_kegiatan' => 'berjalan', 'sumber_dana' => 'hibah lppm', 'nidn_ketua' => '9999999999', 'mitra' => 'X'],
    ], JenisKegiatan::Pkm)])->assertOk()
        ->assertSee('1 baris bergalat')
        ->assertSee('Ketua dengan NIDN/NUPTK 9999999999 tidak ada di Data Dosen.')
        ->assertSee('Sumber dana tidak dikenal')
        ->assertDontSee('name="sertakan_ganda"', false);
    $this->actingAs($operator)->post(route('riwayat-impor.simpan', 'pkm'))->assertSessionHasErrors('berkas');
    expect(RiwayatKegiatan::count())->toBe(0);

    $this->actingAs($operator)->post(route('riwayat-impor.periksa', 'pkm'), ['berkas' => berkasRiwayat([$baris], JenisKegiatan::Pkm)])
        ->assertOk()->assertSee('1 siap disimpan')->assertSee('Mitra baru masuk data master: UMKM Keripik Salak.')
        ->assertSee('Laporan akhir belum diunggah');
    $this->actingAs($operator)->post(route('riwayat-impor.simpan', 'pkm'))
        ->assertRedirect(route('riwayat-dosen.index', ['pkm', 'status' => 'semua']))->assertSessionHas('status', '1 kegiatan berhasil diimpor.');

    $riwayat = RiwayatKegiatan::with(['anggota', 'mitra'])->sole();
    expect($riwayat->only(['jenis_kegiatan', 'status_verifikasi', 'nilai_dana', 'kabupaten_kota_kode', 'jumlah_sasaran', 'diinput_oleh']))->toBe([
        'jenis_kegiatan' => JenisKegiatan::Pkm, 'status_verifikasi' => RiwayatKegiatan::VALID, 'nilai_dana' => 2_500_000,
        'kabupaten_kota_kode' => '3404', 'jumlah_sasaran' => 25, 'diinput_oleh' => $operator->id,
    ])
        ->and($riwayat->anggota->map(fn ($a) => [$a->nama, $a->peran, $a->keterangan])->all())->toBe([
            [$this->ani->namaLengkap(), RiwayatKegiatan::KETUA, null],
            [$this->budi->namaLengkap(), RiwayatKegiatan::ANGGOTA, null],
            ['Ayu Lestari', RiwayatKegiatan::ANGGOTA, '2201001'],
            ['Bagus', RiwayatKegiatan::ANGGOTA, null],
        ])
        ->and($riwayat->mitra->pluck('nama')->all())->toBe(['Kelompok Tani Makmur', 'UMKM Keripik Salak'])
        ->and(Mitra::where('nama', 'UMKM Keripik Salak')->sole()->status)->toBe(Mitra::MASTER)
        ->and($riwayat->daftarTautanMedia())->toBe(['https://koran.example/a', 'https://koran.example/b']);

    $this->actingAs($this->budi->user)->get(route('riwayat.index', 'pkm'))
        ->assertSee('Pelatihan pemasaran daring')->assertSee('Laporan akhir belum diunggah');
});

test('rows like an existing activity are skipped unless kept on purpose', function () {
    riwayatRekap([$this->ani], ['judul' => 'Pemetaan kualitas air sumur warga', 'tahun_mulai' => 2023]);
    $operator = pengguna(Role::ADMIN_LPPM);
    $baris = [
        ['judul' => 'Pemetaan Kualitas Air Sumur Warga', 'tahun_mulai' => '2023', 'status_kegiatan' => 'berjalan', 'sumber_dana' => 'Kemdiktisaintek', 'nidn_ketua' => '0023456789'],
        ['judul' => 'Rancang bangun alat ukur debit', 'tahun_mulai' => '2024', 'status_kegiatan' => 'berjalan', 'sumber_dana' => 'industri', 'nidn_ketua' => '0034567890'],
    ];

    $this->actingAs($operator)->post(route('riwayat-impor.periksa', 'penelitian'), ['berkas' => berkasRiwayat($baris)])
        ->assertOk()->assertSee('Mirip data yang sudah ada')->assertSee('name="sertakan_ganda"', false);
    $this->actingAs($operator)->post(route('riwayat-impor.simpan', 'penelitian'))
        ->assertSessionHas('status', '1 kegiatan berhasil diimpor, 1 baris yang mirip data lain dilewati.');
    expect(RiwayatKegiatan::count())->toBe(2);

    $this->actingAs($operator)->post(route('riwayat-impor.periksa', 'penelitian'), ['berkas' => berkasRiwayat([$baris[0]])]);
    $this->actingAs($operator)->post(route('riwayat-impor.simpan', 'penelitian'), ['sertakan_ganda' => '1'])
        ->assertSessionHas('status', '1 kegiatan berhasil diimpor.');
    expect(RiwayatKegiatan::where('sumber_dana', 'kemdiktisaintek')->count())->toBe(1);
});

test('only LPPM and operators import', function () {
    $this->actingAs($this->ani->user)->get(route('riwayat-impor.create', 'penelitian'))->assertForbidden();
    $this->actingAs(kaprodiRekap($this->informatika->id))->post(route('riwayat-impor.periksa', 'penelitian'))->assertForbidden();
    $this->actingAs(pengguna(Role::ADMIN_LPPM))->get(route('riwayat-dosen.index', 'penelitian'))->assertSee(route('riwayat-impor.create', 'penelitian'));
});
