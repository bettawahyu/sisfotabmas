<?php

namespace App\Services;

use App\Enums\JenisKegiatan;
use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Proposal;
use App\Models\RiwayatKegiatan;
use App\Models\RiwayatKegiatanAnggota;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use OpenSpout\Common\Entity\Style\Style;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel downloads of research and community service records and of their
 * summary. Files are never exported: the final report shows only as
 * uploaded or not.
 */
class EksporRiwayat
{
    /**
     * One row per record, the columns the import template also reads where
     * they overlap.
     *
     * @param  Collection<int, RiwayatKegiatan>  $riwayat
     * @return Collection<int, array<string, mixed>>
     */
    public function baris(Collection $riwayat, JenisKegiatan $jenis): Collection
    {
        return $riwayat->values()->map(function (RiwayatKegiatan $r, int $i) use ($jenis) {
            $ketua = $r->ketua();
            $tipe = fn (TipeAnggota $t) => $r->anggota->filter(fn (RiwayatKegiatanAnggota $a) => $a->tipe === $t && $a->peran !== RiwayatKegiatan::KETUA);

            return [
                'No' => $i + 1,
                'Judul' => $r->judul,
                'Tahun mulai' => $r->tahun_mulai,
                'Tahun selesai' => $r->tahun_selesai,
                'Status kegiatan' => $r->namaStatusKegiatan(),
                'Sumber dana' => $r->namaSumberDana(),
                'Pemberi dana' => $r->pemberi_dana,
                'Skema' => $r->skema,
                'Nilai dana' => $r->nilai_dana,
                'No kontrak' => $r->no_kontrak,
                'Ketua' => $ketua?->nama,
                'NIDN ketua' => $ketua?->dosen?->nidn,
                'Anggota dosen' => $tipe(TipeAnggota::Dosen)->pluck('nama')->join('; '),
                'NIDN anggota' => $tipe(TipeAnggota::Dosen)->map(fn ($a) => $a->dosen?->nidn)->filter()->join(', '),
                'Mahasiswa' => $tipe(TipeAnggota::Mahasiswa)->map(fn ($a) => $a->keterangan ? "{$a->nama} ({$a->keterangan})" : $a->nama)->join('; '),
                'Pihak eksternal' => $tipe(TipeAnggota::Eksternal)->map(fn ($a) => $a->keterangan ? "{$a->nama} ({$a->keterangan})" : $a->nama)->join('; '),
                'Mitra' => $r->mitra->pluck('nama')->join('; '),
                'Kabupaten/kota' => $r->kabupatenKota?->nama,
                'Provinsi' => $r->kabupatenKota?->provinsi?->nama,
                'Alamat lokasi' => $r->alamat_lokasi,
                ...($jenis === JenisKegiatan::Pkm ? [
                    'Jumlah sasaran' => $r->jumlah_sasaran,
                    'Catatan dampak' => $r->catatan_dampak,
                    'Tautan video' => $r->tautan_video,
                    'Publikasi media massa' => implode(' ', $r->daftarTautanMedia()),
                ] : []),
                'Luaran tertaut' => $r->karyaTertaut()->map(fn ($k) => $k->judul)->filter()->join('; '),
                'Laporan akhir' => $r->status_kegiatan === RiwayatKegiatan::SELESAI ? $r->namaStatusLaporan() : '-',
                'Status verifikasi' => $r->namaStatusVerifikasi(),
                'Diinput oleh' => $r->penginput?->name,
            ];
        });
    }

    /**
     * A lecturer's own list: their LPPM grants (under way and finished)
     * followed by their records.
     *
     * @param  array{berlangsung: Collection<int, RiwayatKegiatan|Proposal>, selesai: Collection<int, RiwayatKegiatan|Proposal>}  $daftar
     * @return Collection<int, array<string, mixed>>
     */
    public function milikDosen(array $daftar, JenisKegiatan $jenis): Collection
    {
        $semua = $daftar['berlangsung']->concat($daftar['selesai']);
        $riwayat = $this->baris($semua->whereInstanceOf(RiwayatKegiatan::class), $jenis);
        $kolom = array_keys($riwayat->first() ?? $this->baris(collect([(new RiwayatKegiatan)->setRelations(['anggota' => collect(), 'mitra' => collect(), 'berkas' => collect(), 'publikasi' => collect(), 'hki' => collect(), 'paten' => collect()])]), $jenis)->first());

        $hibah = $semua->whereInstanceOf(Proposal::class)->values()->map(function (Proposal $p) use ($kolom) {
            $baris = array_fill_keys($kolom, null);

            return array_merge($baris, [
                'Judul' => $p->judul,
                'Tahun mulai' => $p->periode->tahun_anggaran,
                'Status kegiatan' => $p->status === StatusProposal::Selesai ? 'Selesai' : 'Sedang berlangsung',
                'Sumber dana' => RekapRiwayat::sumberDana()[RekapRiwayat::HIBAH_LPPM],
                'Pemberi dana' => 'LPPM',
                'Skema' => $p->periode->skema->nama,
                'Nilai dana' => $p->dana_disetujui,
                'Ketua' => $p->ketua->namaLengkap(),
                'NIDN ketua' => $p->ketua->nidn,
                // A grant closes only once its final report is accepted.
                'Laporan akhir' => $p->status === StatusProposal::Selesai ? 'Sudah unggah' : '-',
                'Status verifikasi' => 'Hibah LPPM',
            ]);
        });

        return $hibah->concat($riwayat)->values()->map(fn (array $b, int $i) => ['No' => $i + 1] + $b);
    }

    /**
     * The summary page as sheets: per year, per programme, per lecturer and,
     * for community service, per place.
     *
     * @param  array<string, mixed>  $rekap  RekapRiwayat::hitung()
     */
    public function rekap(array $rekap, JenisKegiatan $jenis): SheetCollection
    {
        $tahun = collect($rekap['perTahun']['baris'])->push(['tahun' => 'Total'] + $rekap['perTahun']['total'])
            ->map(function (array $b) use ($rekap) {
                $baris = ['Tahun' => $b['tahun']];
                foreach ($rekap['perTahun']['sumber'] as $kode => $label) {
                    $baris[$label] = $b['sumber'][$kode];
                }

                return $baris + ['Selesai' => $b['selesai'], 'Berlangsung' => $b['berlangsung'], 'Jumlah' => $b['jumlah'], 'Dana' => $b['dana']];
            });

        $lembar = [
            'Per tahun' => $tahun,
            'Per program studi' => $rekap['perProdi']->map(fn (array $p) => [
                'Program studi' => $p['nama'],
                'Kegiatan' => $p['kegiatan'],
                'Dosen aktif' => $p['dosen_aktif'],
                'Jumlah dosen' => $p['dosen_total'],
                'Persentase dosen aktif' => $p['persen'],
                'Dana (diketuai)' => $p['dana'],
            ]),
            'Per dosen' => $rekap['perDosen']->map(fn (array $d) => [
                'Dosen' => $d['dosen']->nama,
                'NIDN' => $d['dosen']->nidn,
                'Program studi' => $d['dosen']->prodi ? trim("{$d['dosen']->prodi->jenjang} {$d['dosen']->prodi->nama}") : null,
                'Ketua' => $d['ketua'],
                'Anggota' => $d['anggota'],
                'Tahun terakhir' => $d['terakhir'],
            ]),
        ];
        if ($jenis === JenisKegiatan::Pkm) {
            $lembar['Sebaran lokasi'] = $rekap['perWilayah']->flatMap(fn (array $w) => $w['kabupaten']->map(fn (array $k) => [
                'Provinsi' => $w['provinsi'],
                'Kabupaten/kota' => $k['nama'],
                'Kegiatan' => $k['jumlah'],
            ]));
        }

        // An empty sheet still needs its header row to make sense.
        return new SheetCollection(collect($lembar)->map(fn (Collection $baris, string $nama) => $baris->isNotEmpty() ? $baris : collect([[$nama => 'Tidak ada data']])));
    }

    /**
     * @param  Collection<int, array<string, mixed>>|SheetCollection  $data
     */
    public function unduh(Collection|SheetCollection $data, string $dasar): StreamedResponse
    {
        if (! $data instanceof SheetCollection && $data->isEmpty()) {
            $data = collect([['Keterangan' => 'Tidak ada data']]);
        }

        // Titles and names are typed by people: a leading "=" must stay text, not become a formula.
        return (new FastExcel($data))->escapeFormulas()->headerStyle((new Style)->setFontBold())
            ->download($dasar.'-'.Date::now()->format('Ymd-His').'.xlsx');
    }
}
