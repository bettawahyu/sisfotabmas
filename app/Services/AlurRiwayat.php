<?php

namespace App\Services;

use App\Enums\JenisKegiatan;
use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Berkas;
use App\Models\Dosen;
use App\Models\Mitra;
use App\Models\Proposal;
use App\Models\RiwayatKegiatan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Lecturers' research and community service outside the LPPM grant cycle.
 * A lecturer's entry waits for LPPM or an operator; one LPPM or an operator
 * enters is valid at once and shows on the listed lecturers' pages.
 */
class AlurRiwayat
{
    /** Route slugs. */
    public const JENIS = [
        'penelitian' => JenisKegiatan::Penelitian,
        'pkm' => JenisKegiatan::Pkm,
    ];

    public const LABEL = [
        'penelitian' => 'Riwayat Penelitian',
        'pkm' => 'Riwayat PkM',
    ];

    /** LPPM grants still under way show beside the records still running. */
    public const HIBAH_BERLANGSUNG = [StatusProposal::Didanai, StatusProposal::Berjalan, StatusProposal::Dimonev];

    /** Titles at least this similar (percent) count as the same activity. */
    private const AMBANG_MIRIP = 85;

    public function __construct(private Pemberitahu $pemberitahu) {}

    public static function slug(JenisKegiatan $jenis): string
    {
        return array_search($jenis, self::JENIS, true);
    }

    /**
     * LPPM admins (and the LPPM secretary, who holds that role) and operators.
     */
    public static function petugasLppm(User $user): bool
    {
        return $user->hasRole(Role::ADMIN_LPPM, Role::OPERATOR);
    }

    public static function bolehUbah(RiwayatKegiatan $riwayat, User $user): bool
    {
        if (self::petugasLppm($user)) {
            return true;
        }

        return $user->dosen !== null && $riwayat->dicantumkan($user->dosen)
            && $riwayat->status_verifikasi !== RiwayatKegiatan::VALID;
    }

    public static function bolehLihat(RiwayatKegiatan $riwayat, User $user): bool
    {
        return self::petugasLppm($user)
            || ($user->dosen !== null && $riwayat->dicantumkan($user->dosen))
            || ($user->hasRole(Role::PIMPINAN_UNIT) && RiwayatKegiatan::whereKey($riwayat->id)->dalamUnit($user)->exists());
    }

    /**
     * Saves a record with its team and partners, and the uploaded files.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $anggota
     * @param  list<int>  $mitra
     * @param  array{bukti?: ?UploadedFile, laporan?: ?UploadedFile, surat_mitra?: ?UploadedFile, foto?: list<UploadedFile>, hapus_foto?: list<int>}  $berkas
     */
    public function simpan(RiwayatKegiatan $riwayat, array $data, array $anggota, array $mitra, array $berkas, User $oleh): RiwayatKegiatan
    {
        $lppm = self::petugasLppm($oleh);
        $this->pastikan($lppm || $oleh->dosen !== null, 'Lengkapi profil dosen terlebih dahulu.');
        $this->pastikan(! $riwayat->exists || self::bolehUbah($riwayat, $oleh), 'Data yang sudah divalidasi hanya bisa dikoreksi oleh operator LPPM.');

        $baris = $this->barisAnggota($anggota);
        $this->pastikan($baris !== [], 'Isi minimal satu anggota tim.');
        $this->pastikan(collect($baris)->where('peran', RiwayatKegiatan::KETUA)->count() === 1, 'Tandai tepat satu ketua.');
        $dosenTim = collect($baris)->pluck('dosen_id')->filter();
        $this->pastikan($dosenTim->isNotEmpty(), 'Cantumkan minimal satu dosen.');
        $this->pastikan($dosenTim->count() === $dosenTim->unique()->count(), 'Seorang dosen tercantum lebih dari sekali.');
        $this->pastikan($lppm || $dosenTim->contains($oleh->dosen->id), 'Cantumkan diri Anda pada tim.');

        $jenis = $data['jenis_kegiatan'];
        if ($jenis === JenisKegiatan::Pkm) {
            $this->pastikan($mitra !== [], 'Kegiatan PkM wajib mencantumkan minimal satu mitra.');
        }
        $mitraSah = Mitra::bisaDipilih($oleh, $riwayat->exists ? $riwayat->mitra()->pluck('mitra.id')->all() : [])
            ->whereIn('id', $mitra)->pluck('id');
        $this->pastikan($mitraSah->count() === count(array_unique($mitra)), 'Mitra yang dipilih tidak tersedia.');

        if ($jenis === JenisKegiatan::Pkm && $data['status_kegiatan'] === RiwayatKegiatan::SELESAI) {
            $this->syaratPkmSelesai($riwayat, $data, $berkas);
        }

        $riwayat = DB::transaction(function () use ($riwayat, $data, $baris, $mitraSah, $berkas, $oleh, $lppm) {
            $riwayat->fill($data);
            if (! $riwayat->exists) {
                $riwayat->diinput_oleh = $oleh->id;
            }
            $riwayat->forceFill($lppm
                ? ['status_verifikasi' => RiwayatKegiatan::VALID, 'catatan_verifikasi' => null,
                    'diverifikasi_lppm_oleh' => $oleh->id, 'diverifikasi_lppm_at' => now()]
                : ['status_verifikasi' => RiwayatKegiatan::MENUNGGU, 'diverifikasi_lppm_oleh' => null, 'diverifikasi_lppm_at' => null])
                ->save();

            $riwayat->anggota()->delete();
            foreach ($baris as $i => $b) {
                $riwayat->anggota()->create($b + ['urutan' => $i + 1]);
            }
            $riwayat->mitra()->sync($mitraSah->all());
            $this->simpanBerkas($riwayat, $berkas, $oleh);

            return $riwayat->unsetRelation('anggota')->unsetRelation('mitra');
        });

        if (! $lppm) {
            $slug = self::slug($riwayat->jenis_kegiatan);
            $this->pemberitahu->kePeran([Role::ADMIN_LPPM, Role::OPERATOR], self::LABEL[$slug].' menunggu verifikasi',
                "{$oleh->dosen->namaLengkap()} mengisi \"{$riwayat->judul}\".", route('riwayat-dosen.index', [$slug, 'status' => RiwayatKegiatan::MENUNGGU]));
        }

        return $riwayat;
    }

    public function hapus(RiwayatKegiatan $riwayat, User $oleh): void
    {
        $this->pastikan(self::bolehUbah($riwayat, $oleh), 'Data yang sudah divalidasi hanya bisa dikoreksi oleh operator LPPM.');

        $riwayat->delete();
    }

    public function verifikasi(RiwayatKegiatan $riwayat, bool $setuju, ?string $catatan, User $oleh): void
    {
        $this->pastikan($setuju || filled($catatan), 'Tulis alasan data ditolak.');
        $this->pastikan(self::petugasLppm($oleh), 'Hanya LPPM atau operator yang memverifikasi data ini.');
        $this->pastikan($riwayat->status_verifikasi === RiwayatKegiatan::MENUNGGU, 'Data ini tidak sedang menunggu verifikasi.');

        $riwayat->forceFill($setuju
            ? ['status_verifikasi' => RiwayatKegiatan::VALID, 'catatan_verifikasi' => null, 'diverifikasi_lppm_oleh' => $oleh->id, 'diverifikasi_lppm_at' => now()]
            : ['status_verifikasi' => RiwayatKegiatan::DITOLAK, 'catatan_verifikasi' => $catatan])->save();

        $slug = self::slug($riwayat->jenis_kegiatan);
        $label = self::LABEL[$slug];
        if ($setuju) {
            $this->pemberitahu->keUser($riwayat->penginput, $label.' tervalidasi',
                "\"{$riwayat->judul}\" sudah divalidasi LPPM.", route('riwayat.index', $slug));
        } else {
            $this->pemberitahu->keUser($riwayat->penginput, $label.' ditolak',
                "\"{$riwayat->judul}\" ditolak saat verifikasi. Alasan: {$catatan}", route('riwayat.edit', [$slug, $riwayat]));
        }
    }

    /**
     * Records and LPPM grants with a title close to the one being entered,
     * from the year before to the year after, so the same activity is not
     * entered twice (say by its lead and by a member).
     *
     * @return Collection<int, array{judul: string, keterangan: string}>
     */
    public function cariGanda(JenisKegiatan $jenis, string $judul, int $tahun, ?int $kecuali = null): Collection
    {
        $kunci = self::normalkan($judul);
        if ($kunci === '') {
            return collect();
        }

        $riwayat = RiwayatKegiatan::jenis($jenis)
            ->whereBetween('tahun_mulai', [$tahun - 1, $tahun + 1])
            ->when($kecuali, fn (Builder $q) => $q->whereKeyNot($kecuali))
            ->with('anggota')
            ->get()
            ->filter(fn (RiwayatKegiatan $r) => self::mirip($kunci, $r->judul))
            ->map(fn (RiwayatKegiatan $r) => [
                'judul' => $r->judul,
                'keterangan' => "Riwayat {$r->periode()} · ".$r->anggota->pluck('nama')->join(', '),
            ]);

        $hibah = Proposal::whereHas('periode.skema', fn (Builder $q) => $q->where('jenis_kegiatan', $jenis))
            ->whereIn('status', [StatusProposal::Disetujui, ...self::HIBAH_BERLANGSUNG, StatusProposal::Selesai])
            ->whereHas('periode', fn (Builder $q) => $q->whereBetween('tahun_anggaran', [$tahun - 1, $tahun + 1]))
            ->with(['ketua', 'periode'])
            ->get()
            ->filter(fn (Proposal $p) => self::mirip($kunci, $p->judul))
            ->map(fn (Proposal $p) => [
                'judul' => $p->judul,
                'keterangan' => "Hibah LPPM {$p->periode->nama} · Ketua {$p->ketua->namaLengkap()}",
            ]);

        return $riwayat->concat($hibah)->values();
    }

    /**
     * The lecturer's records and LPPM grants of one kind, split into what is
     * still under way and what is done. A grant only joins the history once
     * it is finished.
     *
     * @return array{berlangsung: Collection<int, RiwayatKegiatan|Proposal>, selesai: Collection<int, RiwayatKegiatan|Proposal>}
     */
    public function milikDosen(Dosen $dosen, JenisKegiatan $jenis): array
    {
        $riwayat = RiwayatKegiatan::jenis($jenis)->milikDosen($dosen)
            ->with(['anggota', 'mitra', 'kabupatenKota.provinsi', 'berkas'])
            ->orderByDesc('tahun_mulai')->latest('updated_at')->get();

        $hibah = Proposal::timDosen($dosen)
            ->whereHas('periode.skema', fn (Builder $q) => $q->where('jenis_kegiatan', $jenis))
            ->whereIn('status', [...self::HIBAH_BERLANGSUNG, StatusProposal::Selesai])
            ->with(['periode.skema', 'ketua'])
            ->get();

        return [
            'berlangsung' => $hibah->filter(fn (Proposal $p) => $p->status !== StatusProposal::Selesai)
                ->concat($riwayat->where('status_kegiatan', RiwayatKegiatan::BERJALAN))->values(),
            'selesai' => $hibah->where('status', StatusProposal::Selesai)
                ->concat($riwayat->where('status_kegiatan', RiwayatKegiatan::SELESAI))->values(),
        ];
    }

    public static function normalkan(string $judul): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($judul))));
    }

    private static function mirip(string $kunci, string $judul): bool
    {
        $lain = self::normalkan($judul);
        if ($lain === $kunci) {
            return true;
        }
        similar_text($kunci, $lain, $persen);

        return $persen >= self::AMBANG_MIRIP;
    }

    /**
     * A finished community service needs photos or a video, the partner's
     * statement and a note on its impact.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $berkas
     */
    private function syaratPkmSelesai(RiwayatKegiatan $riwayat, array $data, array $berkas): void
    {
        $fotoLama = $riwayat->exists
            ? $riwayat->berkas()->where('kategori', RiwayatKegiatan::BERKAS_FOTO)->whereNotIn('id', $berkas['hapus_foto'] ?? [])->exists()
            : false;
        $adaSurat = ! empty($berkas['surat_mitra'])
            || ($riwayat->exists && $riwayat->berkas()->where('kategori', RiwayatKegiatan::BERKAS_SURAT_MITRA)->exists());

        $this->pastikan($fotoLama || ! empty($berkas['foto']) || filled($data['tautan_video'] ?? null),
            'Kegiatan PkM yang selesai wajib dilengkapi foto dokumentasi atau tautan video YouTube.');
        $this->pastikan($adaSurat, 'Kegiatan PkM yang selesai wajib dilengkapi surat keterangan mitra.');
        $this->pastikan(filled($data['catatan_dampak'] ?? null), 'Kegiatan PkM yang selesai wajib diisi catatan dampak.');
    }

    /**
     * Drops empty rows and takes a lecturer's name from their profile.
     *
     * @param  list<array<string, mixed>>  $anggota
     * @return list<array{tipe: TipeAnggota, dosen_id: int|null, nama: string, keterangan: string|null, peran: string}>
     */
    private function barisAnggota(array $anggota): array
    {
        $dosen = Dosen::whereIn('id', collect($anggota)->pluck('dosen_id')->filter())->get()->keyBy('id');
        $baris = [];
        foreach ($anggota as $a) {
            $tipe = TipeAnggota::tryFrom($a['tipe'] ?? '') ?? TipeAnggota::Dosen;
            $d = $tipe === TipeAnggota::Dosen && filled($a['dosen_id'] ?? null) ? $dosen->get((int) $a['dosen_id']) : null;
            $nama = $d?->namaLengkap() ?? (filled($a['nama'] ?? null) ? trim($a['nama']) : null);
            if ($nama === null || ($tipe === TipeAnggota::Dosen && $d === null)) {
                continue;
            }
            $baris[] = [
                'tipe' => $tipe,
                'dosen_id' => $d?->id,
                'nama' => $nama,
                'keterangan' => $d === null && filled($a['keterangan'] ?? null) ? trim($a['keterangan']) : null,
                'peran' => ($a['peran'] ?? null) === RiwayatKegiatan::KETUA ? RiwayatKegiatan::KETUA : RiwayatKegiatan::ANGGOTA,
            ];
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $berkas
     */
    private function simpanBerkas(RiwayatKegiatan $riwayat, array $berkas, User $oleh): void
    {
        $tunggal = [
            'bukti' => RiwayatKegiatan::BERKAS_BUKTI,
            'laporan' => RiwayatKegiatan::BERKAS_LAPORAN,
            'surat_mitra' => RiwayatKegiatan::BERKAS_SURAT_MITRA,
        ];
        foreach ($tunggal as $kunci => $kategori) {
            if (! empty($berkas[$kunci])) {
                $this->hapusBerkas($riwayat->berkas()->where('kategori', $kategori)->get());
                $this->unggah($riwayat, $kategori, $berkas[$kunci], $oleh);
            }
        }

        if (! empty($berkas['hapus_foto'])) {
            $this->hapusBerkas($riwayat->berkas()->where('kategori', RiwayatKegiatan::BERKAS_FOTO)->whereIn('id', $berkas['hapus_foto'])->get());
        }
        foreach ($berkas['foto'] ?? [] as $foto) {
            $this->unggah($riwayat, RiwayatKegiatan::BERKAS_FOTO, $foto, $oleh);
        }
    }

    private function unggah(RiwayatKegiatan $riwayat, string $kategori, UploadedFile $file, User $oleh): void
    {
        $riwayat->berkas()->create([
            'kategori' => $kategori,
            'nama_asli' => $file->getClientOriginalName(),
            'path' => $file->store("riwayat/{$riwayat->id}"),
            'mime' => $file->getMimeType(),
            'ukuran_byte' => $file->getSize(),
            'diunggah_oleh' => $oleh->id,
        ]);
    }

    /**
     * @param  Collection<int, Berkas>  $berkas
     */
    private function hapusBerkas(Collection $berkas): void
    {
        foreach ($berkas as $b) {
            Storage::delete($b->path);
            $b->forceDelete();
        }
    }

    private function pastikan(bool $kondisi, string $pesan): void
    {
        if (! $kondisi) {
            throw ValidationException::withMessages(['riwayat' => $pesan]);
        }
    }
}
