<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Publikasi;
use App\Models\RiwayatPendidikan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Lecturers' full qualifications for operators, LPPM and unit heads: the list,
 * narrowed to the viewer's unit and the filter, and the rows of its Excel files.
 */
class DataDosen
{
    public const JENIS_KELAMIN = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    /**
     * Operators and LPPM see every lecturer; a unit head only their unit's.
     */
    public static function bolehLihatSemua(User $user): bool
    {
        return $user->hasRole(Role::ADMIN_LPPM, Role::OPERATOR);
    }

    /**
     * @param  array{cari?: string|null, prodi_id?: int|string|null, jabatan_fungsional?: string|null, pendidikan_terakhir?: string|null}  $filter
     * @return Builder<Dosen>
     */
    public function query(User $user, array $filter = []): Builder
    {
        return Dosen::query()
            ->when(! self::bolehLihatSemua($user), fn (Builder $q) => $q->dalamUnit($user))
            ->when($filter['cari'] ?? null, fn (Builder $q, $cari) => $q->where(fn (Builder $w) => $w
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('nidn', 'like', "%{$cari}%")
                ->orWhere('nuptk', 'like', "%{$cari}%")
                ->orWhere('nip', 'like', "%{$cari}%")))
            ->when($filter['prodi_id'] ?? null, fn (Builder $q, $prodi) => $q->where('prodi_id', $prodi))
            ->when($filter['jabatan_fungsional'] ?? null, fn (Builder $q, $jabatan) => $q->where('jabatan_fungsional', $jabatan))
            ->when($filter['pendidikan_terakhir'] ?? null, fn (Builder $q, $jenjang) => $q->where('pendidikan_terakhir', $jenjang))
            ->with(['user', 'prodi.fakultas', 'riwayatPendidikan'])
            ->orderBy('nama');
    }

    /**
     * The filtered download: Dosen, Riwayat Pendidikan and Publikasi sheets.
     *
     * @param  EloquentCollection<int, Dosen>  $dosen
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function sheetTersaring(EloquentCollection $dosen): array
    {
        $dosen->load(['publikasi' => fn ($q) => $q->with(['kontributor', 'peringkatJurnal'])->orderByDesc('tahun')->orderBy('judul')]);

        $pendidikan = $dosen->flatMap(fn (Dosen $d) => $d->riwayatPendidikan->map(fn (RiwayatPendidikan $r) => [$d, $r]));
        $publikasi = $dosen->flatMap(fn (Dosen $d) => $d->publikasi->map(fn (Publikasi $p) => [$d, $p]));

        return [
            'Dosen' => $dosen->values()->map(fn (Dosen $d, int $i) => $this->identitas($d, $i + 1) + [
                'Jumlah publikasi' => $d->publikasi->count(),
            ]),
            'Riwayat Pendidikan' => $pendidikan->values()->map(fn (array $baris, int $i) => [
                'No' => $i + 1,
                'Nama dosen' => $baris[0]->namaLengkap(),
                'NIDN/NUPTK' => $baris[0]->nidn ?? $baris[0]->nuptk,
                'Jenjang' => $baris[1]->jenjang,
                'Institusi' => $baris[1]->institusi,
                'Program studi' => $baris[1]->program_studi,
                'Negara' => $baris[1]->negara,
                'Tahun lulus' => $baris[1]->tahun_lulus,
                'Judul tugas akhir' => $baris[1]->judul_tugas_akhir,
            ]),
            'Publikasi' => $publikasi->values()->map(fn (array $baris, int $i) => [
                'No' => $i + 1,
                'Nama dosen' => $baris[0]->namaLengkap(),
                'NIDN/NUPTK' => $baris[0]->nidn ?? $baris[0]->nuptk,
                'Tahun' => $baris[1]->tahun,
                'Jenis' => $baris[1]->namaJenis(),
                'Judul' => $baris[1]->judul,
                'Penulis' => $baris[1]->kontributor->pluck('nama_penulis')->implode('; '),
                'Jurnal/prosiding/penerbit' => $baris[1]->nama_wadah,
                'ISSN/ISBN' => $baris[1]->issn_isbn,
                'Volume' => $baris[1]->volume,
                'Nomor' => $baris[1]->nomor,
                'Halaman' => $baris[1]->halaman,
                'Peringkat' => $baris[1]->namaPeringkat(),
                'DOI' => $baris[1]->doi,
                'URL' => $baris[1]->url,
                'Status' => $baris[1]->namaStatus(),
                'Verifikasi' => $baris[1]->namaStatusVerifikasi(),
            ]),
        ];
    }

    /**
     * The full download: one row per lecturer, with each degree's school,
     * programme and year in its own columns.
     *
     * @param  EloquentCollection<int, Dosen>  $dosen
     * @return Collection<int, array<string, mixed>>
     */
    public function sheetLengkap(EloquentCollection $dosen): Collection
    {
        return $dosen->values()->map(function (Dosen $d, int $i) {
            $baris = $this->identitas($d, $i + 1);
            foreach (Dosen::PENDIDIKAN as $jenjang) {
                // A lecturer may hold two degrees of one level; the latest stands for it.
                $r = $d->riwayatPendidikan->where('jenjang', $jenjang)->last();
                $baris += [
                    "{$jenjang} institusi" => $r?->institusi,
                    "{$jenjang} program studi" => $r?->program_studi,
                    "{$jenjang} negara" => $r?->negara,
                    "{$jenjang} tahun lulus" => $r?->tahun_lulus,
                    "{$jenjang} judul tugas akhir" => $r?->judul_tugas_akhir,
                ];
            }

            return $baris;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function identitas(Dosen $d, int $no): array
    {
        return [
            'No' => $no,
            'Nama lengkap' => $d->namaLengkap(),
            'NIDN' => $d->nidn,
            'NUPTK' => $d->nuptk,
            'NIP' => $d->nip,
            'Email' => $d->user?->email,
            'No. HP' => $d->no_hp,
            'Jenis kelamin' => self::JENIS_KELAMIN[$d->jenis_kelamin] ?? null,
            'Tempat lahir' => $d->tempat_lahir,
            'Tanggal lahir' => $d->tgl_lahir?->format('Y-m-d'),
            'Program studi' => $d->prodi ? trim("{$d->prodi->jenjang} {$d->prodi->nama}") : null,
            'Fakultas' => $d->prodi?->fakultas?->nama,
            'Jabatan fungsional' => $d->jabatan_fungsional?->label(),
            'Pendidikan terakhir' => $d->pendidikan_terakhir,
            'Bidang keahlian' => $d->bidang_keahlian,
            'SINTA ID' => $d->sinta_id,
            'Skor SINTA' => $d->skor_sinta === null ? null : (float) $d->skor_sinta,
            'Scopus ID' => $d->scopus_author_id,
            'Google Scholar ID' => $d->google_scholar_id,
            'ORCID' => $d->orcid,
            'H-index Scopus' => $d->h_index_scopus,
            'H-index Scholar' => $d->h_index_scholar,
        ];
    }
}
