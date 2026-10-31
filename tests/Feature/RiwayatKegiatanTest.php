<?php

use App\Enums\JenisKegiatan;
use App\Enums\StatusProposal;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\KabupatenKota;
use App\Models\Mitra;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\RiwayatKegiatan;
use App\Models\Role;
use App\Models\SkemaHibah;
use App\Notifications\Pemberitahuan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * The form fields of a finished self-funded record led by the given lecturer.
 *
 * @return array<string, mixed>
 */
function isianRiwayat(Dosen $ketua, array $ubah = []): array
{
    return $ubah + [
        'judul' => 'Pemetaan kualitas air sumur warga',
        'tahun_mulai' => 2025,
        'status_kegiatan' => RiwayatKegiatan::SELESAI,
        'sumber_dana' => 'mandiri',
        'kabupaten_kota_kode' => '3404',
        'alamat_lokasi' => 'Desa Wedomartani, Kecamatan Ngemplak',
        'anggota' => [
            ['tipe' => 'dosen', 'dosen_id' => $ketua->id],
            ['tipe' => 'mahasiswa', 'nama' => 'Ayu Lestari', 'keterangan' => '2201001'],
            ['tipe' => 'dosen', 'dosen_id' => ''],
        ],
        'ketua' => 0,
        'abaikan_ganda' => 1,
    ];
}

function mitraMaster(string $nama = 'Kelompok Tani Makmur'): Mitra
{
    $mitra = new Mitra(['nama' => $nama, 'bidang' => 'Pertanian', 'is_active' => true]);
    $mitra->forceFill(['status' => Mitra::MASTER])->save();

    return $mitra;
}

beforeEach(function () {
    Storage::fake();
    $teknik = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik']);
    $this->informatika = $teknik->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $this->elektro = $teknik->programStudi()->create(['kode' => 'TE', 'nama' => 'Elektro', 'jenjang' => 'S1']);
    $this->dosen = Dosen::factory()->create(['prodi_id' => $this->informatika->id]);
});

test('the regencies of Indonesia are loaded with their provinces', function () {
    expect(KabupatenKota::count())->toBe(514)
        ->and(KabupatenKota::find('3404')->namaLengkap())->toBe('Kabupaten Sleman, Daerah Istimewa Yogyakarta')
        ->and(KabupatenKota::pilihan())->toHaveCount(38);
});

test('a lecturer records self-funded research that waits for LPPM', function () {
    Notification::fake();
    $operator = pengguna(Role::OPERATOR);
    $user = $this->dosen->user;

    $this->actingAs($user)->get(route('riwayat.create', 'penelitian'))->assertOk()->assertSee('Sumber dana');
    $this->actingAs($user)->post(route('riwayat.store', 'penelitian'), isianRiwayat($this->dosen, [
        'bukti' => UploadedFile::fake()->create('sk.pdf', 50, 'application/pdf'),
    ]))->assertRedirect(route('riwayat.index', 'penelitian'));

    $riwayat = RiwayatKegiatan::sole();
    expect($riwayat->jenis_kegiatan)->toBe(JenisKegiatan::Penelitian)
        ->and($riwayat->status_verifikasi)->toBe(RiwayatKegiatan::MENUNGGU)
        ->and($riwayat->diinput_oleh)->toBe($user->id)
        ->and($riwayat->anggota->pluck('nama')->all())->toBe([$this->dosen->namaLengkap(), 'Ayu Lestari'])
        ->and($riwayat->ketua()->dosen_id)->toBe($this->dosen->id)
        ->and($riwayat->berkas->sole()->kategori)->toBe(RiwayatKegiatan::BERKAS_BUKTI);
    Notification::assertSentTo($operator, Pemberitahuan::class);

    $this->actingAs($user)->get(route('riwayat.index', 'penelitian'))->assertOk()
        ->assertSee($riwayat->judul)->assertSee('Menunggu verifikasi')->assertSee('Kabupaten Sleman');
    // Research and community service are kept apart.
    $this->actingAs($user)->get(route('riwayat.index', 'pkm'))->assertOk()->assertDontSee($riwayat->judul);
});

test('a lecturer must be on the team and cannot touch a validated record', function () {
    $lain = Dosen::factory()->create();
    $user = $this->dosen->user;

    $this->actingAs($user)->post(route('riwayat.store', 'penelitian'), isianRiwayat($lain))->assertSessionHasErrors('riwayat');
    expect(RiwayatKegiatan::count())->toBe(0);

    $this->actingAs($user)->post(route('riwayat.store', 'penelitian'), isianRiwayat($this->dosen));
    $riwayat = RiwayatKegiatan::sole();
    $this->actingAs($lain->user)->get(route('riwayat.edit', ['penelitian', $riwayat]))->assertForbidden();

    $this->actingAs(pengguna(Role::ADMIN_LPPM))->post(route('riwayat-dosen.verifikasi', ['penelitian', $riwayat]), ['aksi' => 'setujui'])->assertRedirect();
    expect($riwayat->fresh()->status_verifikasi)->toBe(RiwayatKegiatan::VALID);

    $this->actingAs($user)->get(route('riwayat.edit', ['penelitian', $riwayat]))->assertForbidden();
    $this->actingAs($user)->delete(route('riwayat.destroy', ['penelitian', $riwayat]))->assertForbidden();
    $this->actingAs($user)->get(route('riwayat.index', 'penelitian'))->assertSee('Valid')->assertDontSee(route('riwayat.edit', ['penelitian', $riwayat]));
});

test('a record LPPM enters is valid at once and shows on every listed lecturer', function () {
    Notification::fake();
    $anggota = Dosen::factory()->create();
    $operator = pengguna(Role::OPERATOR);

    $this->actingAs($operator)->get(route('riwayat.create', 'pkm'))->assertOk();
    $this->actingAs($operator)->post(route('riwayat.store', 'pkm'), isianRiwayat($this->dosen, [
        'status_kegiatan' => RiwayatKegiatan::BERJALAN,
        'sumber_dana' => 'pemerintah_daerah',
        'pemberi_dana' => 'Pemkab Sleman',
        'mitra' => [mitraMaster()->id],
        'anggota' => [['tipe' => 'dosen', 'dosen_id' => $this->dosen->id], ['tipe' => 'dosen', 'dosen_id' => $anggota->id]],
    ]))->assertRedirect(route('riwayat-dosen.index', 'pkm'));

    $riwayat = RiwayatKegiatan::sole();
    expect($riwayat->status_verifikasi)->toBe(RiwayatKegiatan::VALID)
        ->and($riwayat->diverifikasi_lppm_oleh)->toBe($operator->id);
    Notification::assertNothingSent();

    foreach ([$this->dosen, $anggota] as $d) {
        $this->actingAs($d->user)->get(route('riwayat.index', 'pkm'))->assertOk()
            ->assertSeeInOrder(['Sedang berlangsung', $riwayat->judul, 'Riwayat'])->assertSee('Kelompok Tani Makmur');
    }
});

test('LPPM grants show as under way and join the history once finished', function () {
    $periode = PeriodeHibah::factory()->create(['skema_id' => SkemaHibah::factory()->pkm()]);
    $berjalan = Proposal::factory()->create(['periode_id' => $periode->id, 'ketua_id' => $this->dosen->id, 'status' => StatusProposal::Berjalan, 'judul' => 'Pendampingan UMKM batik']);
    $selesai = Proposal::factory()->create(['periode_id' => $periode->id, 'ketua_id' => $this->dosen->id, 'status' => StatusProposal::Selesai, 'judul' => 'Literasi digital karang taruna']);
    $draf = Proposal::factory()->create(['periode_id' => $periode->id, 'ketua_id' => $this->dosen->id, 'judul' => 'Draf yang belum diajukan']);

    $this->actingAs($this->dosen->user)->get(route('riwayat.index', 'pkm'))->assertOk()
        ->assertSeeInOrder(['Sedang berlangsung', $berjalan->judul, 'Riwayat', $selesai->judul])
        ->assertDontSee($draf->judul);
    $this->actingAs($this->dosen->user)->get(route('riwayat.index', 'penelitian'))->assertDontSee($berjalan->judul);
});

test('finished community service needs documentation, the partner letter and an impact note', function () {
    $user = $this->dosen->user;
    $isian = isianRiwayat($this->dosen, ['mitra' => [mitraMaster()->id]]);

    $this->actingAs($user)->post(route('riwayat.store', 'pkm'), array_merge($isian, ['mitra' => []]))
        ->assertSessionHasErrors(['riwayat' => 'Kegiatan PkM wajib mencantumkan minimal satu mitra.']);
    $this->actingAs($user)->post(route('riwayat.store', 'pkm'), $isian)
        ->assertSessionHasErrors(['riwayat' => 'Kegiatan PkM yang selesai wajib dilengkapi foto dokumentasi atau tautan video YouTube.']);
    $this->actingAs($user)->post(route('riwayat.store', 'pkm'), $isian + ['tautan_video' => 'https://youtu.be/abc123'])
        ->assertSessionHasErrors(['riwayat' => 'Kegiatan PkM yang selesai wajib dilengkapi surat keterangan mitra.']);
    expect(RiwayatKegiatan::count())->toBe(0);

    $this->actingAs($user)->post(route('riwayat.store', 'pkm'), $isian + [
        'foto' => [UploadedFile::fake()->create('kegiatan.jpg', 200, 'image/jpeg')],
        'surat_mitra' => UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf'),
        'catatan_dampak' => 'Warga kini menguji air sumur sendiri.',
        'tautan_media' => "https://koran.example/berita-1\nhttps://tv.example/berita-2",
        'jumlah_sasaran' => 40,
    ])->assertSessionHasNoErrors();

    $riwayat = RiwayatKegiatan::sole();
    expect($riwayat->berkas->pluck('kategori')->sort()->values()->all())->toBe([RiwayatKegiatan::BERKAS_FOTO, RiwayatKegiatan::BERKAS_SURAT_MITRA])
        ->and($riwayat->daftarTautanMedia())->toHaveCount(2);

    $foto = $riwayat->berkas->firstWhere('kategori', RiwayatKegiatan::BERKAS_FOTO);
    $this->actingAs($user)->get(route('riwayat.berkas', $foto))->assertOk();
    $this->actingAs(Dosen::factory()->create()->user)->get(route('riwayat.berkas', $foto))->assertNotFound();
});

test('a similar title in a nearby year is flagged before saving', function () {
    $user = $this->dosen->user;
    $this->actingAs($user)->post(route('riwayat.store', 'penelitian'), isianRiwayat($this->dosen));

    $this->actingAs($user)->postJson(route('riwayat.cek-ganda', 'penelitian'), ['judul' => 'Pemetaan Kualitas Air Sumur Warga.', 'tahun_mulai' => 2026])
        ->assertOk()->assertJsonCount(1, 'ganda')->assertJsonPath('ganda.0.judul', 'Pemetaan kualitas air sumur warga');
    $this->actingAs($user)->postJson(route('riwayat.cek-ganda', 'penelitian'), ['judul' => 'Pemetaan kualitas air sumur warga', 'tahun_mulai' => 2020])
        ->assertJsonCount(0, 'ganda');
    $this->actingAs($user)->postJson(route('riwayat.cek-ganda', 'pkm'), ['judul' => 'Pemetaan kualitas air sumur warga', 'tahun_mulai' => 2025])
        ->assertJsonCount(0, 'ganda');

    // A form sent without the modal check comes back with the warning.
    $isian = isianRiwayat($this->dosen);
    unset($isian['abaikan_ganda']);
    $this->actingAs($user)->post(route('riwayat.store', 'penelitian'), $isian)->assertSessionHas('ganda');
    expect(RiwayatKegiatan::count())->toBe(1);
    $this->actingAs($user)->post(route('riwayat.store', 'penelitian'), $isian + ['abaikan_ganda' => 1]);
    expect(RiwayatKegiatan::count())->toBe(2);
});

test('a partner a lecturer adds is used at once and waits for LPPM to join the register', function () {
    $user = $this->dosen->user;
    $lain = Dosen::factory()->create()->user;
    $admin = pengguna(Role::ADMIN_LPPM);

    $this->actingAs($user)->postJson(route('mitra.usulan'), ['nama' => 'UMKM Batik Sekar', 'bidang' => 'Kerajinan batik', 'kontak' => '0812000111'])
        ->assertCreated()->assertJsonPath('status', Mitra::USULAN);
    $usulan = Mitra::sole();
    // Typing the same name again picks the same partner.
    $this->actingAs($user)->postJson(route('mitra.usulan'), ['nama' => ' umkm batik sekar', 'bidang' => 'Batik'])->assertOk()->assertJsonPath('id', $usulan->id);
    expect(Mitra::count())->toBe(1);

    $this->actingAs($user)->post(route('riwayat.store', 'pkm'), isianRiwayat($this->dosen, [
        'status_kegiatan' => RiwayatKegiatan::BERJALAN, 'mitra' => [$usulan->id],
    ]))->assertSessionHasNoErrors();
    // Another lecturer cannot pick it until LPPM takes it in.
    $this->actingAs($lain)->get(route('riwayat.create', 'pkm'))->assertDontSee('UMKM Batik Sekar');
    $this->actingAs($user)->get(route('mitra.index'))->assertForbidden();

    $this->actingAs($admin)->get(route('mitra.index'))->assertOk()->assertSee('UMKM Batik Sekar')->assertSee('Terima');
    $this->actingAs($admin)->post(route('mitra.verifikasi', $usulan), ['aksi' => 'terima'])->assertRedirect();
    expect($usulan->fresh()->status)->toBe(Mitra::MASTER);
    $this->actingAs($lain)->get(route('riwayat.create', 'pkm'))->assertSee('UMKM Batik Sekar');

    $this->actingAs($admin)->post(route('mitra.store'), ['nama' => 'SD Negeri 1 Ngemplak', 'bidang' => 'Pendidikan dasar', 'kabupaten_kota_kode' => '3404', 'is_active' => 1])
        ->assertRedirect(route('mitra.index'));
    expect(Mitra::where('nama', 'SD Negeri 1 Ngemplak')->value('status'))->toBe(Mitra::MASTER);
});

test('LPPM sees every record, a unit head only their unit and cannot verify', function () {
    $user = $this->dosen->user;
    $elektro = Dosen::factory()->create(['prodi_id' => $this->elektro->id]);
    $this->actingAs($user)->post(route('riwayat.store', 'penelitian'), isianRiwayat($this->dosen));
    $this->actingAs($elektro->user)->post(route('riwayat.store', 'penelitian'), isianRiwayat($elektro, ['judul' => 'Rancang bangun inverter surya']));
    [$milikTi, $milikTe] = RiwayatKegiatan::orderBy('id')->get()->all();

    $this->actingAs(pengguna(Role::ADMIN_LPPM))->get(route('riwayat-dosen.index', 'penelitian'))->assertOk()
        ->assertSee($milikTi->judul)->assertSee($milikTe->judul)->assertSee('Validasi');

    $kaprodi = pengguna(Role::PIMPINAN_UNIT);
    $kaprodi->roles()->updateExistingPivot(Role::where('kode', Role::PIMPINAN_UNIT)->value('id'), ['prodi_id' => $this->informatika->id]);
    $this->actingAs($kaprodi)->get(route('riwayat-dosen.index', 'penelitian'))->assertOk()
        ->assertSee($milikTi->judul)->assertDontSee($milikTe->judul)->assertDontSee('Validasi');
    $this->actingAs($kaprodi)->post(route('riwayat-dosen.verifikasi', ['penelitian', $milikTi]), ['aksi' => 'setujui'])->assertForbidden();

    $this->actingAs($user)->get(route('riwayat-dosen.index', 'penelitian'))->assertForbidden();
});

test('a rejected record goes back to the lecturer and waits again after changes', function () {
    Notification::fake();
    $user = $this->dosen->user;
    $this->actingAs($user)->post(route('riwayat.store', 'penelitian'), isianRiwayat($this->dosen));
    $riwayat = RiwayatKegiatan::sole();

    $this->actingAs(pengguna(Role::OPERATOR))->post(route('riwayat-dosen.verifikasi', ['penelitian', $riwayat]), ['aksi' => 'tolak'])->assertSessionHasErrors('riwayat');
    $this->actingAs(pengguna(Role::OPERATOR))->post(route('riwayat-dosen.verifikasi', ['penelitian', $riwayat]), ['aksi' => 'tolak', 'catatan' => 'Lampirkan SK.']);
    expect($riwayat->fresh()->status_verifikasi)->toBe(RiwayatKegiatan::DITOLAK);
    Notification::assertSentTo($user, Pemberitahuan::class);
    $this->actingAs($user)->get(route('riwayat.index', 'penelitian'))->assertSee('Lampirkan SK.');

    $this->actingAs($user)->get(route('riwayat.edit', ['penelitian', $riwayat]))->assertOk()->assertSee($riwayat->judul);
    $this->actingAs($user)->put(route('riwayat.update', ['penelitian', $riwayat]), isianRiwayat($this->dosen, [
        'bukti' => UploadedFile::fake()->create('sk.pdf', 50, 'application/pdf'),
    ]))->assertRedirect(route('riwayat.index', 'penelitian'));
    expect($riwayat->fresh()->status_verifikasi)->toBe(RiwayatKegiatan::MENUNGGU);
});

test('the sidebar links the new pages', function () {
    $this->actingAs($this->dosen->user)->get(route('riwayat.index', 'penelitian'))
        ->assertSee('Riwayat Penelitian')->assertSee('Riwayat PkM');
    $this->actingAs(pengguna(Role::ADMIN_LPPM))->get(route('mitra.index'))
        ->assertSee(route('riwayat-dosen.index', 'penelitian'))->assertSee(route('mitra.index'));
});
