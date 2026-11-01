<?php

namespace App\Services;

use App\Enums\JenisKegiatan;
use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Dosen;
use App\Models\KabupatenKota;
use App\Models\ProgramStudi;
use App\Models\Proposal;
use App\Models\RiwayatKegiatan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The research and community service summary: LPPM grants (funded through
 * finished) taken from the proposals, together with the validated records
 * of activities outside them. A year is the year the activity began; for a
 * grant, its budget year.
 */
class RekapRiwayat
{
    /** The funding source key that stands for an LPPM grant. */
    public const HIBAH_LPPM = 'hibah_lppm';

    /**
     * Who sees every lecturer; a unit head sees only their unit's.
     */
    public static function bolehLihatSemua(User $user): bool
    {
        return $user->hasRole(Role::ADMIN_LPPM, Role::OPERATOR, Role::PIMPINAN);
    }

    /**
     * @return array<string, string>
     */
    public static function sumberDana(): array
    {
        return [self::HIBAH_LPPM => 'Hibah LPPM'] + RiwayatKegiatan::SUMBER_DANA;
    }

    /**
     * @return EloquentCollection<int, ProgramStudi>
     */
    public function prodi(User $user): EloquentCollection
    {
        return self::bolehLihatSemua($user)
            ? ProgramStudi::with('fakultas')->orderBy('nama')->get()
            : app(DataDosen::class)->prodi($user);
    }

    /**
     * @param  array{tahun_dari: int, tahun_sampai: int, prodi_id?: int|string|null, sumber_dana?: string|null}  $filter
     * @return array<string, mixed>
     */
    public function hitung(User $user, JenisKegiatan $jenis, array $filter): array
    {
        // The lecturers this summary is about: the viewer's unit, narrowed by programme.
        $dosen = Dosen::query()
            ->when(! self::bolehLihatSemua($user), fn (Builder $q) => $q->dalamUnit($user))
            ->when($filter['prodi_id'] ?? null, fn (Builder $q, $prodi) => $q->where('prodi_id', $prodi))
            ->with('prodi')
            ->orderBy('nama')
            ->get()
            ->keyBy('id');

        $kegiatan = $this->kegiatan($jenis, $filter)
            // An activity counts here when one of those lecturers is on its team.
            ->filter(fn (array $k) => array_intersect_key($k['dosen'], $dosen->all()) !== [])
            ->values();

        return [
            'ringkas' => [
                'jumlah' => $kegiatan->count(),
                'selesai' => $kegiatan->where('selesai', true)->count(),
                'berlangsung' => $kegiatan->where('selesai', false)->count(),
                'dosen' => $kegiatan->flatMap(fn (array $k) => array_keys($k['dosen']))->unique()->intersect($dosen->keys())->count(),
                'dana' => $kegiatan->sum('dana'),
                'menunggu' => $this->menunggu($jenis, $filter, $dosen->keys()->all()),
            ],
            'perTahun' => $this->perTahun($kegiatan, $filter),
            'perProdi' => $this->perProdi($kegiatan, $dosen),
            'perDosen' => $this->perDosen($kegiatan, $dosen),
            'jumlahMitra' => $kegiatan->flatMap(fn (array $k) => $k['mitra'])->unique()->count(),
            'perWilayah' => $jenis === JenisKegiatan::Pkm ? $this->perWilayah($kegiatan) : collect(),
        ];
    }

    /**
     * Grants and validated records alike, as plain rows.
     *
     * @param  array<string, mixed>  $filter
     * @return Collection<int, array{sumber: string, tahun: int, selesai: bool, dana: int, dosen: array<int, string>, mitra: list<int>, wilayah: list<string>}>
     */
    private function kegiatan(JenisKegiatan $jenis, array $filter): Collection
    {
        $sumber = $filter['sumber_dana'] ?? null;
        $hibah = $sumber !== null && $sumber !== self::HIBAH_LPPM ? collect() : Proposal::query()
            ->whereIn('status', [...AlurRiwayat::HIBAH_BERLANGSUNG, StatusProposal::Selesai])
            ->whereHas('periode', fn (Builder $q) => $q->whereBetween('tahun_anggaran', [$filter['tahun_dari'], $filter['tahun_sampai']])
                ->whereHas('skema', fn (Builder $s) => $s->where('jenis_kegiatan', $jenis)))
            ->with(['periode', 'anggota', 'mitra.mitra'])
            ->get()
            ->map(function (Proposal $p) {
                $tim = [$p->ketua_id => RiwayatKegiatan::KETUA];
                foreach ($p->anggota as $a) {
                    if ($a->tipe === TipeAnggota::Dosen && $a->dosen_id && $a->dikonfirmasi_at && ! $a->ditolak_at) {
                        $tim[$a->dosen_id] ??= 'anggota';
                    }
                }

                return [
                    'sumber' => self::HIBAH_LPPM,
                    'tahun' => (int) $p->periode->tahun_anggaran,
                    'selesai' => $p->status === StatusProposal::Selesai,
                    'dana' => (int) $p->dana_disetujui,
                    'dosen' => $tim,
                    'mitra' => $p->mitra->pluck('mitra_id')->filter()->values()->all(),
                    'wilayah' => $p->mitra->pluck('mitra.kabupaten_kota_kode')->filter()->unique()->values()->all(),
                ];
            });

        $riwayat = $sumber === self::HIBAH_LPPM ? collect() : $this->riwayat($jenis, $filter)
            ->where('status_verifikasi', RiwayatKegiatan::VALID)
            ->with(['anggota', 'mitra'])
            ->get()
            ->map(fn (RiwayatKegiatan $r) => [
                'sumber' => $r->sumber_dana,
                'tahun' => (int) $r->tahun_mulai,
                'selesai' => $r->status_kegiatan === RiwayatKegiatan::SELESAI,
                'dana' => (int) $r->nilai_dana,
                'dosen' => $r->anggota->filter(fn ($a) => $a->dosen_id !== null)
                    ->mapWithKeys(fn ($a) => [$a->dosen_id => $a->peran === RiwayatKegiatan::KETUA ? RiwayatKegiatan::KETUA : 'anggota'])->all(),
                'mitra' => $r->mitra->modelKeys(),
                'wilayah' => array_values(array_filter([$r->kabupaten_kota_kode])),
            ]);

        return $hibah->concat($riwayat)->values();
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<RiwayatKegiatan>
     */
    private function riwayat(JenisKegiatan $jenis, array $filter): Builder
    {
        return RiwayatKegiatan::jenis($jenis)
            ->whereBetween('tahun_mulai', [$filter['tahun_dari'], $filter['tahun_sampai']])
            ->when($filter['sumber_dana'] ?? null, fn (Builder $q, $sumber) => $q->where('sumber_dana', $sumber));
    }

    /**
     * Records still waiting for LPPM, shown apart and left out of every count.
     *
     * @param  array<string, mixed>  $filter
     * @param  list<int>  $dosen
     */
    private function menunggu(JenisKegiatan $jenis, array $filter, array $dosen): int
    {
        if (($filter['sumber_dana'] ?? null) === self::HIBAH_LPPM) {
            return 0;
        }

        return $this->riwayat($jenis, $filter)
            ->where('status_verifikasi', RiwayatKegiatan::MENUNGGU)
            ->whereHas('anggota', fn (Builder $a) => $a->whereIn('dosen_id', $dosen))
            ->count();
    }

    /**
     * One row a year, newest first, with a count for each funding source.
     *
     * @param  Collection<int, array<string, mixed>>  $kegiatan
     * @param  array<string, mixed>  $filter
     * @return array{sumber: array<string, string>, baris: list<array<string, mixed>>, total: array<string, mixed>}
     */
    private function perTahun(Collection $kegiatan, array $filter): array
    {
        // Only the sources that occur, so the table stays narrow.
        $sumber = array_intersect_key(self::sumberDana(), $kegiatan->pluck('sumber')->flip()->all());
        $baris = [];
        foreach (range($filter['tahun_sampai'], $filter['tahun_dari']) as $tahun) {
            $setahun = $kegiatan->where('tahun', $tahun);
            $baris[] = [
                'tahun' => $tahun,
                'sumber' => array_map(fn ($kode) => $setahun->where('sumber', $kode)->count(), array_combine(array_keys($sumber), array_keys($sumber))),
                'selesai' => $setahun->where('selesai', true)->count(),
                'berlangsung' => $setahun->where('selesai', false)->count(),
                'jumlah' => $setahun->count(),
                'dana' => $setahun->sum('dana'),
            ];
        }

        return [
            'sumber' => $sumber,
            'baris' => $baris,
            'total' => [
                'sumber' => array_map(fn ($kode) => $kegiatan->where('sumber', $kode)->count(), array_combine(array_keys($sumber), array_keys($sumber))),
                'selesai' => $kegiatan->where('selesai', true)->count(),
                'berlangsung' => $kegiatan->where('selesai', false)->count(),
                'jumlah' => $kegiatan->count(),
                'dana' => $kegiatan->sum('dana'),
            ],
        ];
    }

    /**
     * Per programme: the activities its lecturers took part in, how many of
     * them were active, and the funds of the activities they led.
     *
     * @param  Collection<int, array<string, mixed>>  $kegiatan
     * @param  EloquentCollection<int, Dosen>  $dosen
     * @return Collection<int, array<string, mixed>>
     */
    private function perProdi(Collection $kegiatan, EloquentCollection $dosen): Collection
    {
        return $dosen->groupBy('prodi_id')->map(function (EloquentCollection $anggota) use ($kegiatan) {
            $id = $anggota->modelKeys();
            $ikut = $kegiatan->filter(fn (array $k) => array_intersect(array_keys($k['dosen']), $id) !== []);
            $aktif = $ikut->flatMap(fn (array $k) => array_keys($k['dosen']))->unique()->intersect($id)->count();
            $prodi = $anggota->first()->prodi;

            return [
                'nama' => $prodi ? trim("{$prodi->jenjang} {$prodi->nama}") : 'Tanpa program studi',
                'kegiatan' => $ikut->count(),
                'dosen_aktif' => $aktif,
                'dosen_total' => $anggota->count(),
                'persen' => (int) round($aktif / $anggota->count() * 100),
                'dana' => $ikut->filter(fn (array $k) => in_array(array_search(RiwayatKegiatan::KETUA, $k['dosen'], true), $id, true))->sum('dana'),
            ];
        })->sortBy('nama')->values();
    }

    /**
     * Each lecturer with at least one activity, busiest first.
     *
     * @param  Collection<int, array<string, mixed>>  $kegiatan
     * @param  EloquentCollection<int, Dosen>  $dosen
     * @return Collection<int, array<string, mixed>>
     */
    private function perDosen(Collection $kegiatan, EloquentCollection $dosen): Collection
    {
        $hasil = [];
        foreach ($kegiatan as $k) {
            foreach ($k['dosen'] as $id => $peran) {
                if (! $dosen->has($id)) {
                    continue;
                }
                $hasil[$id] ??= ['dosen' => $dosen[$id], 'ketua' => 0, 'anggota' => 0, 'terakhir' => 0];
                $hasil[$id][$peran === RiwayatKegiatan::KETUA ? 'ketua' : 'anggota']++;
                $hasil[$id]['terakhir'] = max($hasil[$id]['terakhir'], $k['tahun']);
            }
        }

        return collect($hasil)->values()
            ->sortBy([fn ($a, $b) => ($b['ketua'] + $b['anggota']) <=> ($a['ketua'] + $a['anggota']), fn ($a, $b) => $a['dosen']->nama <=> $b['dosen']->nama])
            ->values();
    }

    /**
     * Community service activities by province, then regency or city.
     *
     * @param  Collection<int, array<string, mixed>>  $kegiatan
     * @return Collection<int, array{provinsi: string, jumlah: int, kabupaten: Collection<int, array{nama: string, jumlah: int}>}>
     */
    private function perWilayah(Collection $kegiatan): Collection
    {
        $jumlah = $kegiatan->flatMap(fn (array $k) => $k['wilayah'])->countBy();
        if ($jumlah->isEmpty()) {
            return collect();
        }
        $wilayah = KabupatenKota::with('provinsi')->whereIn('kode', $jumlah->keys())->get();

        return $wilayah->groupBy('provinsi_kode')->map(fn (EloquentCollection $kab) => [
            'provinsi' => $kab->first()->provinsi->nama,
            'jumlah' => $kab->sum(fn (KabupatenKota $k) => $jumlah[$k->kode]),
            'kabupaten' => $kab->map(fn (KabupatenKota $k) => ['nama' => $k->nama, 'jumlah' => $jumlah[$k->kode]])->sortByDesc('jumlah')->values(),
        ])->sortByDesc('jumlah')->values();
    }
}
