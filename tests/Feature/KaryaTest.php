<?php

use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Hki;
use App\Models\Luaran;
use App\Models\Paten;
use App\Models\Publikasi;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * The form fields of a journal article with the given rows of authors.
 *
 * @param  list<array<string, mixed>>  $penulis
 * @return array<string, mixed>
 */
function isianPublikasi(array $penulis, array $ubah = []): array
{
    return $ubah + [
        'jenis' => 'jurnal_nasional',
        'judul' => 'Irigasi tetes berbasis sensor kelembapan',
        'nama_wadah' => 'Jurnal Teknik Pertanian',
        'tahun' => now()->year,
        'peringkat' => 'sinta_2',
        'status' => 'published',
        'doi' => '10.1234/jtp.2026.12',
        'kontributor' => $penulis,
        'korespondensi' => 0,
    ];
}

beforeEach(function () {
    $teknik = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik']);
    $this->informatika = $teknik->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $this->elektro = $teknik->programStudi()->create(['kode' => 'TE', 'nama' => 'Elektro', 'jenjang' => 'S1']);
    $this->dosen = Dosen::factory()->create(['prodi_id' => $this->informatika->id]);
});

test('a lecturer records a publication with an outside co-author', function () {
    $user = $this->dosen->user;

    $this->actingAs($user)->get(route('karya.publikasi.create'))->assertOk()->assertSee('Korespondensi');
    $this->actingAs($user)->post(route('karya.publikasi.store'), isianPublikasi([
        ['dosen_id' => $this->dosen->id],
        ['nama' => 'Rina Hartati', 'afiliasi' => 'Universitas Lain'],
        ['dosen_id' => '', 'nama' => ''],
    ]))->assertRedirect(route('karya.publikasi.index'));

    $publikasi = Publikasi::sole();
    expect($publikasi->diinput_oleh)->toBe($user->id)
        ->and($publikasi->status_verifikasi)->toBe(Publikasi::MENUNGGU)
        ->and($publikasi->penulis->pluck('nama_penulis')->all())->toBe([$this->dosen->namaLengkap(), 'Rina Hartati'])
        ->and($publikasi->penulis->pluck('urutan')->all())->toBe([1, 2])
        ->and($publikasi->penulis->first()->is_korespondensi)->toBeTrue();

    $this->actingAs($user)->get(route('karya.publikasi.index'))->assertOk()
        ->assertSee($publikasi->judul)->assertSee('Rina Hartati')->assertSee('Menunggu kaprodi');
});

test('a lecturer must be listed and only edits their own entries', function () {
    $user = $this->dosen->user;
    $lain = Dosen::factory()->create();

    $this->actingAs($user)->post(route('karya.hki.store'), [
        'jenis' => 'hak_cipta', 'judul' => 'Aplikasi pencatat logbook', 'status' => 'diajukan',
        'kontributor' => [['dosen_id' => $lain->id]],
    ])->assertSessionHasErrors('karya');
    expect(Hki::count())->toBe(0);

    $paten = Paten::factory()->create(['diinput_oleh' => $lain->user_id]);
    $paten->inventor()->create(['dosen_id' => $lain->id, 'nama' => $lain->nama, 'urutan' => 1]);

    $this->actingAs($user)->get(route('karya.paten.edit', $paten))->assertForbidden();
    $this->actingAs($user)->delete(route('karya.paten.destroy', $paten))->assertForbidden();
    $this->actingAs($user)->get(route('karya.paten.index'))->assertDontSee($paten->judul);
    $this->actingAs($lain->user)->get(route('karya.paten.edit', $paten))->assertOk()->assertSee($paten->judul);
});

test('the kaprodi of the unit approves, then LPPM validates', function () {
    Notification::fake();
    $this->actingAs($this->dosen->user)->post(route('karya.publikasi.store'), isianPublikasi([['dosen_id' => $this->dosen->id]]));
    $publikasi = Publikasi::sole();
    $kaprodiTi = pimpinanUnit('prodi:'.$this->informatika->id);
    $kaprodiTe = pimpinanUnit('prodi:'.$this->elektro->id);
    $admin = pengguna(Role::ADMIN_LPPM);

    $this->actingAs($kaprodiTe)->get(route('verifikasi-karya.index'))->assertOk()->assertDontSee($publikasi->judul);
    $this->actingAs($kaprodiTe)->post(route('verifikasi-karya.store', ['publikasi', $publikasi->id]), ['aksi' => 'setujui'])
        ->assertSessionHasErrors('karya');
    $this->actingAs($admin)->get(route('verifikasi-karya.index'))->assertOk()->assertDontSee($publikasi->judul);

    $this->actingAs($kaprodiTi)->get(route('verifikasi-karya.index'))->assertSee($publikasi->judul);
    $this->actingAs($kaprodiTi)->post(route('verifikasi-karya.store', ['publikasi', $publikasi->id]), ['aksi' => 'setujui'])
        ->assertSessionHasNoErrors();
    expect($publikasi->fresh()->status_verifikasi)->toBe(Publikasi::DISETUJUI_KAPRODI);
    Notification::assertSentTo($admin, Pemberitahuan::class);

    $this->actingAs($admin)->get(route('verifikasi-karya.index'))->assertSee($publikasi->judul);
    $this->actingAs($admin)->post(route('verifikasi-karya.store', ['publikasi', $publikasi->id]), ['aksi' => 'setujui'])
        ->assertSessionHasNoErrors();
    expect($publikasi->fresh())
        ->status_verifikasi->toBe(Publikasi::VALID)
        ->diverifikasi_kaprodi_oleh->toBe($kaprodiTi->id)
        ->diverifikasi_lppm_oleh->toBe($admin->id);
    expect(Publikasi::valid()->count())->toBe(1);

    $this->actingAs($this->dosen->user)->put(route('karya.publikasi.update', $publikasi), isianPublikasi([['dosen_id' => $this->dosen->id]], ['judul' => 'Judul baru']))
        ->assertSessionHasErrors('karya');
    $this->actingAs($this->dosen->user)->delete(route('karya.publikasi.destroy', $publikasi))->assertSessionHasErrors('karya');
    expect($publikasi->fresh()->judul)->not->toBe('Judul baru');
});

test('a rejected entry tells the lecturer why and goes back to the kaprodi once fixed', function () {
    Notification::fake();
    $this->actingAs($this->dosen->user)->post(route('karya.publikasi.store'), isianPublikasi([['dosen_id' => $this->dosen->id]]));
    $publikasi = Publikasi::sole();
    $kaprodi = pimpinanUnit('prodi:'.$this->informatika->id);

    $this->actingAs($kaprodi)->post(route('verifikasi-karya.store', ['publikasi', $publikasi->id]), ['aksi' => 'tolak'])
        ->assertSessionHasErrors('karya');
    $this->actingAs($kaprodi)->post(route('verifikasi-karya.store', ['publikasi', $publikasi->id]), ['aksi' => 'tolak', 'catatan' => 'DOI tidak ditemukan.']);
    expect($publikasi->fresh()->status_verifikasi)->toBe(Publikasi::DITOLAK);
    Notification::assertSentTo($this->dosen->user, Pemberitahuan::class);
    $this->actingAs($this->dosen->user)->get(route('karya.publikasi.index'))->assertSee('DOI tidak ditemukan.');

    $this->actingAs($this->dosen->user)->put(route('karya.publikasi.update', $publikasi), isianPublikasi([['dosen_id' => $this->dosen->id]], ['doi' => '10.1234/jtp.2026.13']))
        ->assertRedirect(route('karya.publikasi.index'));
    expect($publikasi->fresh())->status_verifikasi->toBe(Publikasi::MENUNGGU)->doi->toBe('10.1234/jtp.2026.13');
});

test('the lead links an output to their own publication', function () {
    Storage::fake();
    $proposal = proposalBerjalan();
    $ketua = $proposal->ketua;
    $luaran = $proposal->luaran()->where('kategori', 'wajib')->sole();
    $milikKetua = Publikasi::factory()->olehDosen($ketua)->create(['judul' => 'Model prediksi debit irigasi']);
    $milikLain = Publikasi::factory()->olehDosen($this->dosen)->create(['judul' => 'Sistem pakar hama padi']);

    $this->actingAs($ketua->user)->get(route('pelaksanaan.show', $proposal))->assertOk()
        ->assertSee($milikKetua->judul)->assertDontSee($milikLain->judul);

    $this->actingAs($ketua->user)->put(route('pelaksanaan.luaran.update', [$proposal, $luaran]), ['status_capaian' => 'terbit', 'karya_id' => $milikLain->id])
        ->assertSessionHasErrors('pelaksanaan');
    $this->actingAs($ketua->user)->put(route('pelaksanaan.luaran.update', [$proposal, $luaran]), ['status_capaian' => 'terbit', 'karya_id' => $milikKetua->id])
        ->assertSessionHasNoErrors();

    expect($luaran->fresh())->publikasi_id->toBe($milikKetua->id)->status_validasi->toBe(Luaran::MENUNGGU);
    $this->actingAs(pengguna(Role::OPERATOR))->get(route('verifikasi.index'))->assertSee($milikKetua->judul);
    $this->actingAs($ketua->user)->get(route('karya.publikasi.index'))->assertSee($proposal->judul);
    $this->actingAs($ketua->user)->delete(route('karya.publikasi.destroy', $milikKetua))->assertSessionHasErrors('karya');
    expect($milikKetua->fresh()->trashed())->toBeFalse();
});

test('publications, HKI and patents sit in tabs', function () {
    $this->actingAs($this->dosen->user)->get(route('karya.hki.index'))->assertOk()
        ->assertSee('role="tablist"', false)
        ->assertSee(route('karya.publikasi.index'), false)
        ->assertSee(route('karya.paten.index'), false)
        ->assertSee('aria-selected="true"', false);

    $admin = User::factory()->create();
    $admin->assignRole(Role::ADMIN_LPPM);
    $this->actingAs($admin)->get(route('verifikasi-karya.index'))->assertOk()
        ->assertSee('role="tablist"', false)
        ->assertSee('role="tabpanel"', false);
});
