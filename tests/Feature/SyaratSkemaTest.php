<?php

use App\Enums\JabatanFungsional;
use App\Enums\JenisKegiatan;
use App\Enums\TipeAnggota;
use App\Models\Dosen;
use App\Models\JenisLuaran;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\Publikasi;
use App\Models\Role;
use App\Models\SkemaHibah;
use App\Models\User;
use App\Services\AlurProposal;

function akunSyarat(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function jenisLuaran(string $kode): JenisLuaran
{
    return JenisLuaran::firstWhere('kode', $kode);
}

test('output types come from reference data with the codes outputs already use', function () {
    expect(JenisLuaran::pluck('kode')->all())->toContain('artikel_jurnal', 'hki', 'testimoni_mitra')
        ->and(jenisLuaran('prosiding')->modelKarya())->toBe(Publikasi::class)
        ->and(jenisLuaran('video_kegiatan')->modelKarya())->toBeNull();

    $luaran = Proposal::factory()->lengkap()->create()->luaran()->first();
    expect($luaran->namaJenis())->toBe('Artikel jurnal');
});

test('the system admin adds an output type and its code locks once used', function () {
    $admin = akunSyarat(Role::ADMIN_SISTEM);

    $this->actingAs($admin)->post(route('sistem.jenis-luaran.store'), [
        'kode' => 'modul_pelatihan', 'nama' => 'Modul pelatihan', 'berlaku_untuk' => 'pkm', 'urutan' => 95, 'is_active' => '1',
    ])->assertRedirect(route('sistem.jenis-luaran.index'));
    expect(jenisLuaran('modul_pelatihan')->berlaku_untuk)->toBe('pkm');

    Proposal::factory()->lengkap()->create();
    $artikel = jenisLuaran('artikel_jurnal');
    $this->actingAs($admin)->put(route('sistem.jenis-luaran.update', $artikel), [
        'kode' => 'artikel', 'nama' => 'Artikel jurnal ilmiah', 'berlaku_untuk' => 'semua', 'tautan_karya' => 'publikasi', 'urutan' => 10, 'is_active' => '1',
    ])->assertSessionHasNoErrors();
    expect($artikel->fresh())->kode->toBe('artikel_jurnal')->nama->toBe('Artikel jurnal ilmiah');

    $this->actingAs(akunSyarat(Role::ADMIN_LPPM))->get(route('sistem.jenis-luaran.index'))->assertForbidden();
});

test('the output dropdown offers active types that fit the activity', function () {
    jenisLuaran('testimoni_mitra')->update(['berlaku_untuk' => 'pkm']);
    jenisLuaran('video_kegiatan')->update(['is_active' => false]);
    $proposal = Proposal::factory()->create();

    $this->actingAs($proposal->ketua->user)->get(route('proposal.show', $proposal))->assertOk()
        ->assertSee('Artikel jurnal')->assertDontSee('Testimoni mitra')->assertDontSee('Video kegiatan');

    $this->actingAs($proposal->ketua->user)->post(route('proposal.luaran.store', $proposal), ['kategori' => 'wajib', 'jenis' => 'testimoni_mitra'])
        ->assertSessionHasErrors('jenis');
    $this->actingAs($proposal->ketua->user)->post(route('proposal.luaran.store', $proposal), ['kategori' => 'wajib', 'jenis' => 'prosiding'])
        ->assertSessionHasNoErrors();
});

test('LPPM sets a scheme requirements and required outputs', function () {
    $admin = akunSyarat(Role::ADMIN_LPPM);
    $skema = SkemaHibah::factory()->create(['jenis_kegiatan' => JenisKegiatan::Penelitian]);

    $this->actingAs($admin)->get(route('admin.skema.edit', $skema))->assertOk()->assertSee('Syarat pengusul')->assertSee('Artikel jurnal');

    $this->actingAs($admin)->put(route('admin.skema.update', $skema), [
        'kode' => $skema->kode, 'nama' => $skema->nama, 'jenis_kegiatan' => 'penelitian', 'sumber_dana' => 'internal',
        'lama_maks_tahun' => 1, 'dana_maksimal_default' => 15_000_000, 'is_active' => '1',
        'jabatan_min' => 'lektor', 'pendidikan_min' => 'S2', 'anggota_dosen_min' => 1, 'anggota_dosen_maks' => 2, 'anggota_mahasiswa_min' => 1,
        'luaran_wajib_jenis' => [jenisLuaran('artikel_jurnal')->id, jenisLuaran('hki')->id],
        'luaran_wajib_jumlah' => [jenisLuaran('artikel_jurnal')->id => 2],
    ])->assertRedirect(route('admin.skema.index'));

    $skema->refresh();
    expect($skema->jabatan_min)->toBe(JabatanFungsional::Lektor)
        ->and($skema->anggota_dosen_maks)->toBe(2)
        ->and($skema->jenisLuaranWajib->pluck('pivot.jumlah_min', 'kode')->all())->toBe(['artikel_jurnal' => 2, 'hki' => 1]);

    $this->actingAs($admin)->put(route('admin.skema.update', $skema), [
        'kode' => $skema->kode, 'nama' => $skema->nama, 'jenis_kegiatan' => 'penelitian', 'sumber_dana' => 'internal',
        'lama_maks_tahun' => 1, 'dana_maksimal_default' => 15_000_000, 'anggota_dosen_min' => 3, 'anggota_dosen_maks' => 1,
    ])->assertSessionHasErrors('anggota_dosen_maks');
});

test('a lecturer below the scheme rank sees why in a modal and cannot start a proposal', function () {
    $periode = PeriodeHibah::factory()->create();
    $periode->skema->update(['jabatan_min' => JabatanFungsional::Lektor, 'pendidikan_min' => 'S2']);
    $dosen = Dosen::factory()->create(['jabatan_fungsional' => JabatanFungsional::AsistenAhli, 'pendidikan_terakhir' => 'S2']);

    $this->actingAs($dosen->user)->get(route('proposal.index'))->assertOk()
        ->assertSee('Ketua minimal Lektor')
        ->assertSee('Belum bisa mengajukan')
        ->assertSee('jabatan fungsional Anda Asisten Ahli')
        ->assertDontSee(route('proposal.create', $periode));

    $this->actingAs($dosen->user)->get(route('proposal.create', $periode))->assertRedirect(route('proposal.index'));
    $this->actingAs($dosen->user)->post(route('proposal.store', $periode), ['judul' => 'Uji'])->assertSessionHasErrors();
    expect(Proposal::count())->toBe(0);

    $dosen->update(['jabatan_fungsional' => JabatanFungsional::LektorKepala]);
    $user = $dosen->user->fresh();
    $this->actingAs($user)->get(route('proposal.index'))->assertSee(route('proposal.create', $periode))->assertDontSee('Belum bisa mengajukan');
    $this->actingAs($user)->get(route('proposal.create', $periode))->assertOk();
});

test('the team size and required outputs are checked before sending', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    $skema = $proposal->periode->skema;
    $skema->update(['anggota_dosen_min' => 1, 'anggota_mahasiswa_min' => 1]);
    $skema->jenisLuaranWajib()->attach([jenisLuaran('artikel_jurnal')->id => ['jumlah_min' => 1], jenisLuaran('hki')->id => ['jumlah_min' => 1]]);

    $alur = app(AlurProposal::class);
    expect($alur->kekurangan($proposal->fresh()))->toContain(
        'Skema ini mensyaratkan minimal 1 anggota dosen (sekarang 0).',
        'Skema ini mensyaratkan minimal 1 anggota mahasiswa (sekarang 0).',
        'Luaran wajib skema: 1 HKI (sekarang 0).',
    )->not->toContain('Luaran wajib skema: 1 Artikel jurnal (sekarang 0).');

    $anggota = Dosen::factory()->create();
    $proposal->anggota()->create(['tipe' => TipeAnggota::Dosen, 'dosen_id' => $anggota->id, 'nama' => $anggota->nama])
        ->forceFill(['dikonfirmasi_at' => now()])->save();
    $proposal->anggota()->create(['tipe' => TipeAnggota::Mahasiswa, 'nama' => 'Siti', 'nim' => '2201001']);
    $proposal->luaran()->create(['kategori' => 'wajib', 'jenis' => 'hki']);

    expect($alur->kekurangan($proposal->fresh()))->toBe([]);
});

test('a team larger than the scheme allows cannot be sent', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    $proposal->periode->skema->update(['anggota_dosen_maks' => 0]);
    $anggota = Dosen::factory()->create();
    $proposal->anggota()->create(['tipe' => TipeAnggota::Dosen, 'dosen_id' => $anggota->id, 'nama' => $anggota->nama, 'dikonfirmasi_at' => now()]);

    expect(app(AlurProposal::class)->kekurangan($proposal->fresh()))->toContain('Skema ini membatasi anggota dosen paling banyak 0 (sekarang 1).');
});

test('the highest degree follows the education history', function () {
    $user = akunSyarat(Role::DOSEN);
    $this->actingAs($user)->put(route('profil-dosen.update'), ['nama' => 'Ani'])->assertSessionHasNoErrors();

    $this->actingAs($user)->post(route('profil-dosen.pendidikan.store'), ['jenjang' => 'S1', 'institusi' => 'Universitas A', 'tahun_lulus' => 2010])
        ->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('profil-dosen.pendidikan.store'), ['jenjang' => 'S3', 'institusi' => 'Universitas B', 'tahun_lulus' => 2020]);
    expect($user->fresh()->dosen->pendidikan_terakhir)->toBe('S3')
        ->and($user->fresh()->dosen->pendidikanSetidaknya('S2'))->toBeTrue();

    // Correcting the S3 entry to S2 lowers the highest degree with it.
    $s3 = $user->dosen->riwayatPendidikan()->firstWhere('jenjang', 'S3');
    $this->actingAs($user)->put(route('profil-dosen.pendidikan.update', $s3), ['jenjang' => 'S2', 'institusi' => 'Universitas B']);
    expect($user->fresh()->dosen->pendidikan_terakhir)->toBe('S2');

    $this->actingAs($user)->delete(route('profil-dosen.pendidikan.destroy', $s3));
    expect($user->fresh()->dosen->pendidikan_terakhir)->toBe('S1');

    $this->actingAs($user)->post(route('profil-dosen.pendidikan.store'), ['jenjang' => 'D3', 'institusi' => 'Politeknik C'])
        ->assertSessionHasErrors('jenjang');
    // The highest degree can no longer be typed in by hand.
    $this->actingAs($user)->put(route('profil-dosen.update'), ['nama' => 'Ani', 'pendidikan_terakhir' => 'S3']);
    expect($user->fresh()->dosen->pendidikan_terakhir)->toBe('S1');
});

test('a lecturer cannot touch another lecturer\'s education history', function () {
    $pemilik = akunSyarat(Role::DOSEN);
    $riwayat = Dosen::factory()->create(['user_id' => $pemilik->id])->riwayatPendidikan()->create(['jenjang' => 'S2', 'institusi' => 'Universitas A']);
    $lain = akunSyarat(Role::DOSEN);
    Dosen::factory()->create(['user_id' => $lain->id]);

    $this->actingAs($lain)->get(route('profil-dosen.pendidikan.edit', $riwayat))->assertNotFound();
    $this->actingAs($lain)->delete(route('profil-dosen.pendidikan.destroy', $riwayat))->assertNotFound();
    expect($riwayat->fresh())->not->toBeNull();
});

test('the qualification page lists the education history', function () {
    $user = akunSyarat(Role::DOSEN);
    Dosen::factory()->create(['user_id' => $user->id])->riwayatPendidikan()->create(['jenjang' => 'S2', 'institusi' => 'Institut Teknologi Bandung']);

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertOk()
        ->assertSee('Riwayat Pendidikan')->assertSee('Institut Teknologi Bandung');
});
