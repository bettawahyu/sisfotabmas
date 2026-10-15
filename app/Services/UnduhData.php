<?php

namespace App\Services;

use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Hki;
use App\Models\Paten;
use App\Models\Proposal;
use App\Models\Publikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Rows for the Excel downloads: every sent proposal, and the publications, HKI
 * and patents, each narrowed by year, type and the lecturers' study programme.
 */
class UnduhData
{
    /**
     * The date that gives an HKI or patent its year: the grant or recording
     * date, else the filing date.
     */
    private const KOLOM_TANGGAL = [
        'hki' => ['tgl_pencatatan', 'tgl_permohonan'],
        'paten' => ['tgl_pemberian', 'tgl_penerimaan'],
    ];

    /**
     * One row per proposal that has left draft.
     *
     * @param  array{tahun?: int|string|null, skema_id?: int|string|null, prodi_id?: int|string|null}  $filter
     * @return Collection<int, array<string, mixed>>
     */
    public function proposal(array $filter): Collection
    {
        $proposal = Proposal::query()
            ->where('status', '!=', StatusProposal::Draft)
            ->when($filter['tahun'] ?? null, fn (Builder $q, $tahun) => $q->whereHas('periode', fn (Builder $p) => $p->where('tahun_anggaran', $tahun)))
            ->when($filter['skema_id'] ?? null, fn (Builder $q, $skema) => $q->whereHas('periode', fn (Builder $p) => $p->where('skema_id', $skema)))
            ->when($filter['prodi_id'] ?? null, fn (Builder $q, $prodi) => $q->whereHas('ketua', fn (Builder $d) => $d->where('prodi_id', $prodi)))
            ->with(['periode.skema', 'ketua.prodi.fakultas', 'bidangFokus', 'anggota'])
            ->get()
            ->sortBy([['periode.tahun_anggaran', 'desc'], ['periode.skema.nama', 'asc'], ['judul', 'asc']])
            ->values();

        return $proposal->map(function (Proposal $p, int $i) {
            $anggota = $p->anggota->whereNull('ditolak_at');

            return [
                'No' => $i + 1,
                'Tahun' => $p->periode->tahun_anggaran,
                'Skema' => $p->periode->skema->nama,
                'Jenis kegiatan' => $p->periode->skema->jenis_kegiatan->label(),
                'Periode' => $p->periode->nama,
                'Judul' => $p->judul,
                'Ketua' => $p->ketua->namaLengkap(),
                'NIDN/NUPTK' => $p->ketua->nidn ?? $p->ketua->nuptk,
                'Program studi' => $p->ketua->prodi?->nama,
                'Fakultas' => $p->ketua->prodi?->fakultas?->nama,
                'Anggota dosen' => $anggota->where('tipe', TipeAnggota::Dosen)->pluck('nama')->implode('; '),
                'Anggota mahasiswa' => $anggota->where('tipe', TipeAnggota::Mahasiswa)->pluck('nama')->implode('; '),
                'Bidang fokus' => $p->bidangFokus?->nama,
                'Lama (tahun)' => $p->lama_tahun,
                'Dana diusulkan' => $p->dana_diusulkan,
                'Dana disetujui' => $p->dana_disetujui,
                'Nilai akhir' => $p->nilai_akhir === null ? null : (float) $p->nilai_akhir,
                'Status' => $p->status->label(),
                'Diajukan' => $p->diajukan_at?->format('Y-m-d'),
            ];
        });
    }

    /**
     * Rows per sheet: Publikasi, HKI and Paten, or only the one the type picks.
     *
     * @param  array{tahun?: int|string|null, jenis?: string|null, prodi_id?: int|string|null, hanya_valid?: bool}  $filter
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function karya(array $filter): array
    {
        [$kelompok, $subjenis] = array_pad(explode(':', (string) ($filter['jenis'] ?? '')), 2, null);

        $sheet = [];
        foreach (AlurKarya::JENIS as $slug => $model) {
            if ($kelompok && $kelompok !== $slug) {
                continue;
            }

            $karya = $this->queryKarya($slug, $model, $filter, $subjenis)->get();
            $sheet[AlurKarya::LABEL[$slug]] = $karya->values()->map(fn (Model $k, int $i) => $this->barisKarya($slug, $k, $i + 1));
        }

        return $sheet;
    }

    /**
     * Types to pick from, grouped by kind; the value is "kind" or "kind:type".
     *
     * @return array<string, array<string, string>>
     */
    public static function pilihanJenisKarya(): array
    {
        return [
            'publikasi' => Publikasi::JENIS,
            'hki' => Hki::JENIS,
            'paten' => Paten::JENIS,
        ];
    }

    /**
     * @param  class-string<Publikasi|Hki|Paten>  $model
     * @param  array<string, mixed>  $filter
     * @return Builder<Publikasi|Hki|Paten>
     */
    private function queryKarya(string $slug, string $model, array $filter, ?string $subjenis): Builder
    {
        $tahun = $filter['tahun'] ?? null;

        return $model::query()
            ->when($filter['hanya_valid'] ?? false, fn (Builder $q) => $q->valid())
            ->when($subjenis, fn (Builder $q, $jenis) => $q->where('jenis', $jenis))
            ->when($filter['prodi_id'] ?? null, fn (Builder $q, $prodi) => $q->whereHas('kontributor.dosen', fn (Builder $d) => $d->where('prodi_id', $prodi)))
            ->when($tahun, function (Builder $q) use ($slug, $tahun) {
                if ($slug === 'publikasi') {
                    $q->where('tahun', $tahun);
                } else {
                    [$utama, $cadangan] = self::KOLOM_TANGGAL[$slug];
                    $q->whereRaw("COALESCE({$utama}, {$cadangan}) BETWEEN ? AND ?", ["{$tahun}-01-01", "{$tahun}-12-31"]);
                }
            })
            ->with(['kontributor.dosen.prodi', ...($slug === 'publikasi' ? ['peringkatJurnal'] : [])])
            ->orderBy('judul');
    }

    /**
     * @return array<string, mixed>
     */
    private function barisKarya(string $slug, Model $k, int $no): array
    {
        $nama = fn ($n) => $slug === 'publikasi' ? $n->nama_penulis : $n->nama;
        $prodi = $k->kontributor->map(fn ($n) => $n->dosen?->prodi?->nama)->filter()->unique()->implode('; ');

        $baris = match ($slug) {
            'publikasi' => [
                'No' => $no,
                'Tahun' => $k->tahun,
                'Jenis' => $k->namaJenis(),
                'Judul' => $k->judul,
                'Penulis' => $k->kontributor->map($nama)->implode('; '),
                'Program studi' => $prodi,
                'Jurnal/prosiding/penerbit' => $k->nama_wadah,
                'ISSN/ISBN' => $k->issn_isbn,
                'Volume' => $k->volume,
                'Nomor' => $k->nomor,
                'Halaman' => $k->halaman,
                'Peringkat' => $k->namaPeringkat(),
                'DOI' => $k->doi,
                'URL' => $k->url,
                'Status' => $k->namaStatus(),
            ],
            'hki' => [
                'No' => $no,
                'Tahun' => ($k->tgl_pencatatan ?? $k->tgl_permohonan)?->year,
                'Jenis' => $k->namaJenis(),
                'Jenis ciptaan' => $k->jenis_ciptaan,
                'Judul' => $k->judul,
                'Pencipta' => $k->kontributor->map($nama)->implode('; '),
                'Program studi' => $prodi,
                'Pemegang hak' => $k->pemegang_hak,
                'No. permohonan' => $k->no_permohonan,
                'Tgl. permohonan' => $k->tgl_permohonan?->format('Y-m-d'),
                'No. pencatatan' => $k->no_pencatatan,
                'Tgl. pencatatan' => $k->tgl_pencatatan?->format('Y-m-d'),
                'URL PDKI' => $k->url_pdki,
                'Status' => $k->namaStatus(),
            ],
            'paten' => [
                'No' => $no,
                'Tahun' => ($k->tgl_pemberian ?? $k->tgl_penerimaan)?->year,
                'Jenis' => $k->namaJenis(),
                'Judul' => $k->judul,
                'Inventor' => $k->kontributor->map($nama)->implode('; '),
                'Program studi' => $prodi,
                'Pemegang paten' => $k->pemegang_paten,
                'No. permohonan' => $k->no_permohonan,
                'Tgl. penerimaan' => $k->tgl_penerimaan?->format('Y-m-d'),
                'No. paten' => $k->no_paten,
                'Tgl. pemberian' => $k->tgl_pemberian?->format('Y-m-d'),
                'URL PDKI' => $k->url_pdki,
                'Status' => $k->namaStatus(),
            ],
        };

        return $baris + ['Verifikasi' => $k->namaStatusVerifikasi()];
    }
}
