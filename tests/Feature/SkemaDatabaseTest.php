<?php

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('every table in the design doc exists', function (string $tabel) {
    expect(Schema::hasTable($tabel))->toBeTrue();
})->with([
    'users', 'roles', 'user_roles', 'notifications', 'fakultas', 'program_studi', 'bidang_fokus',
    'skema_hibah', 'periode_hibah', 'kriteria_penilaian', 'berkas',
    'dosen', 'riwayat_pendidikan',
    'publikasi', 'publikasi_penulis',
    'kekayaan_intelektual', 'ki_inventor',
    'proposal', 'proposal_anggota', 'proposal_mitra', 'rab_item', 'proposal_status_log', 'pencairan_dana',
    'pengumuman',
    'luaran', 'penilaian', 'nilai_penilaian', 'laporan', 'logbook',
]);

test('the lecturer profile carries the external identifiers', function () {
    expect(Schema::hasColumns('dosen', ['scopus_author_id', 'google_scholar_id', 'orcid', 'skor_sinta']))->toBeTrue()
        ->and(Schema::hasColumns('user_roles', ['unit_type', 'unit_id']))->toBeTrue();
});

test('an output links to a real publication only', function () {
    $proposal = Proposal::factory()->create();

    expect(fn () => DB::table('luaran')->insert([
        'proposal_id' => $proposal->id,
        'kategori' => 'wajib',
        'jenis' => 'artikel_jurnal',
        'status_capaian' => 'direncanakan',
        'publikasi_id' => 999,
    ]))->toThrow(QueryException::class);
});

test('a reviewer scores a proposal once per stage', function () {
    $proposal = Proposal::factory()->create();
    $baris = ['proposal_id' => $proposal->id, 'reviewer_id' => User::factory()->create()->id, 'tahap' => 'seleksi'];

    DB::table('penilaian')->insert($baris);

    expect(fn () => DB::table('penilaian')->insert($baris))->toThrow(QueryException::class);
});
