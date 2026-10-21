<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Hki;
use App\Models\Paten;
use App\Models\Publikasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lecturers keep their publications, HKI and patents; LPPM or an operator
 * checks each entry and marks it valid.
 */
class AlurKarya
{
    /**
     * The three kinds of entry, keyed by the slug used in routes and views.
     *
     * @var array<string, class-string<Publikasi|Hki|Paten>>
     */
    public const JENIS = [
        'publikasi' => Publikasi::class,
        'hki' => Hki::class,
        'paten' => Paten::class,
    ];

    public const LABEL = [
        'publikasi' => 'Publikasi',
        'hki' => 'HKI',
        'paten' => 'Paten',
    ];

    /** Feather icons for the tabs. */
    public const IKON = [
        'publikasi' => 'file-text',
        'hki' => 'award',
        'paten' => 'zap',
    ];

    /** Contributors' name column differs between the three tables. */
    private const KOLOM_NAMA = [
        'publikasi' => 'nama_penulis',
        'hki' => 'nama',
        'paten' => 'nama',
    ];

    public function __construct(private Pemberitahu $pemberitahu) {}

    public static function slug(Publikasi|Hki|Paten $karya): string
    {
        return array_search($karya::class, self::JENIS, true);
    }

    /**
     * Saves an entry with its contributors in the given order. Any change sends
     * the entry back to LPPM.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array{dosen_id?: int|string|null, nama?: string|null, afiliasi?: string|null, is_korespondensi?: bool}>  $kontributor
     */
    public function simpan(Publikasi|Hki|Paten $karya, array $data, array $kontributor, User $oleh): Publikasi|Hki|Paten
    {
        $this->pastikan($oleh->dosen !== null, 'Lengkapi profil dosen terlebih dahulu.');
        $this->pastikan($karya->status_verifikasi !== Hki::VALID, 'Data yang sudah divalidasi LPPM tidak bisa diubah.');

        $baris = $this->barisKontributor($kontributor);
        $this->pastikan($baris !== [], 'Isi minimal satu nama.');
        $this->pastikan(collect($baris)->contains('dosen_id', $oleh->dosen->id), 'Cantumkan diri Anda pada daftar nama.');

        $karya = DB::transaction(function () use ($karya, $data, $baris, $oleh) {
            $karya->fill($data);
            if (! $karya->exists) {
                $karya->diinput_oleh = $oleh->id;
            }
            $karya->forceFill([
                'status_verifikasi' => Hki::MENUNGGU,
                'diverifikasi_lppm_oleh' => null,
                'diverifikasi_lppm_at' => null,
            ])->save();

            $kolom = self::KOLOM_NAMA[self::slug($karya)];
            $karya->kontributor()->delete();
            foreach ($baris as $i => $b) {
                $isian = ['dosen_id' => $b['dosen_id'], $kolom => $b['nama'], 'afiliasi' => $b['afiliasi'], 'urutan' => $i + 1];
                if ($karya instanceof Publikasi) {
                    $isian['is_korespondensi'] = $b['is_korespondensi'];
                }
                $karya->kontributor()->create($isian);
            }

            return $karya->unsetRelation('kontributor');
        });

        $label = self::LABEL[self::slug($karya)];
        $this->pemberitahu->kePeran([Role::ADMIN_LPPM, Role::OPERATOR], $label.' menunggu verifikasi',
            "{$oleh->dosen->namaLengkap()} mengisi \"{$karya->judul}\".", route('karya-dosen.index', ['tab' => self::slug($karya), 'status' => Hki::MENUNGGU]));

        return $karya;
    }

    public function hapus(Publikasi|Hki|Paten $karya): void
    {
        $this->pastikan($karya->status_verifikasi !== Hki::VALID, 'Data yang sudah divalidasi LPPM tidak bisa dihapus.');
        $this->pastikan(! $karya->luaran()->exists(), 'Data ini sudah ditautkan ke luaran proposal.');

        $karya->delete();
    }

    /**
     * LPPM or an operator validates an entry waiting for verification, or
     * rejects it with a note.
     */
    public function verifikasi(Publikasi|Hki|Paten $karya, bool $setuju, ?string $catatan, User $oleh): void
    {
        $this->pastikan($setuju || filled($catatan), 'Tulis alasan data ditolak.');
        $this->pastikan(self::bolehVerifikasi($oleh), 'Hanya LPPM atau operator yang memverifikasi data ini.');
        $this->pastikan($karya->status_verifikasi === Hki::MENUNGGU, 'Data ini tidak sedang menunggu verifikasi.');

        $karya->forceFill($setuju
            ? ['status_verifikasi' => Hki::VALID, 'catatan_verifikasi' => null, 'diverifikasi_lppm_oleh' => $oleh->id, 'diverifikasi_lppm_at' => now()]
            : ['status_verifikasi' => Hki::DITOLAK, 'catatan_verifikasi' => $catatan])->save();

        $slug = self::slug($karya);
        if ($setuju) {
            $this->pemberitahu->keUser($karya->penginput, self::LABEL[$slug].' tervalidasi',
                "\"{$karya->judul}\" sudah divalidasi LPPM.", route("karya.{$slug}.index"));
        } else {
            $this->pemberitahu->keUser($karya->penginput, self::LABEL[$slug].' ditolak',
                "\"{$karya->judul}\" ditolak saat verifikasi. Alasan: {$catatan}", route("karya.{$slug}.edit", $karya));
        }
    }

    public static function bolehVerifikasi(User $user): bool
    {
        return $user->hasRole(Role::ADMIN_LPPM, Role::OPERATOR);
    }

    /**
     * Drops empty rows and fills a lecturer's name from their profile.
     *
     * @param  list<array<string, mixed>>  $kontributor
     * @return list<array{dosen_id: int|null, nama: string, afiliasi: string|null, is_korespondensi: bool}>
     */
    private function barisKontributor(array $kontributor): array
    {
        $dosen = Dosen::whereIn('id', collect($kontributor)->pluck('dosen_id')->filter())->get()->keyBy('id');
        $baris = [];
        foreach ($kontributor as $k) {
            $d = filled($k['dosen_id'] ?? null) ? $dosen->get((int) $k['dosen_id']) : null;
            $nama = filled($k['nama'] ?? null) ? trim($k['nama']) : $d?->namaLengkap();
            if ($nama === null) {
                continue;
            }
            $baris[] = [
                'dosen_id' => $d?->id,
                'nama' => $nama,
                'afiliasi' => filled($k['afiliasi'] ?? null) ? trim($k['afiliasi']) : null,
                'is_korespondensi' => (bool) ($k['is_korespondensi'] ?? false),
            ];
        }

        return $baris;
    }

    private function pastikan(bool $kondisi, string $pesan): void
    {
        if (! $kondisi) {
            throw ValidationException::withMessages(['karya' => $pesan]);
        }
    }
}
