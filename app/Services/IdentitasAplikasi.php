<?php

namespace App\Services;

use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * The app's name, logo, favicon and landing page content, kept by the system
 * admin in the pengaturan table. Anything left empty falls back to the default.
 */
class IdentitasAplikasi
{
    /** Uploaded images, keyed by the slug used in routes. */
    public const BERKAS = [
        'logo' => 'identitas.logo',
        'logo-gelap' => 'identitas.logo_gelap',
        'favicon' => 'identitas.favicon',
        'latar' => 'landing.latar',
    ];

    public const DESKRIPSI_BAWAAN = 'Sistem informasi penelitian dan pengabdian kepada masyarakat dosen: profil dosen, '
        .'publikasi, HKI dan paten, pengajuan proposal, pengumuman, serta monitoring dan evaluasi.';

    private const KUNCI = [
        'identitas.nama', 'identitas.nama_singkat', 'landing.deskripsi',
        'identitas.logo', 'identitas.logo_gelap', 'identitas.favicon', 'landing.latar', 'akun.pendaftaran',
    ];

    private const CACHE = 'identitas-aplikasi';

    /** @var array<string, string|null>|null */
    private ?array $nilai = null;

    public function nama(): string
    {
        return $this->ambil('identitas.nama') ?? config('app.nama_bawaan', config('app.name'));
    }

    /** The short name shown next to the logo in the sidebar. */
    public function namaSingkat(): string
    {
        return $this->ambil('identitas.nama_singkat') ?? $this->nama();
    }

    public function deskripsi(): string
    {
        return $this->ambil('landing.deskripsi') ?? self::DESKRIPSI_BAWAAN;
    }

    /** Whether visitors may create their own lecturer account. Open until the admin closes it. */
    public function pendaftaranDibuka(): bool
    {
        return $this->ambil('akun.pendaftaran') !== '0';
    }

    /** The value the admin saved, without a fallback, for filling the form. */
    public function tersimpan(string $kunci): ?string
    {
        return $this->ambil($kunci);
    }

    /** Stored path of an uploaded image, or null when the default is in use. */
    public function berkas(string $jenis): ?string
    {
        return $this->ambil(self::BERKAS[$jenis]);
    }

    /**
     * Public URL of an uploaded image. The dark-background logo falls back to the main logo.
     */
    public function url(string $jenis): ?string
    {
        $path = $this->berkas($jenis);
        if ($path === null && $jenis === 'logo-gelap') {
            return $this->url('logo');
        }

        return $path === null ? null : route('identitas.berkas', $jenis).'?v='.substr(md5($path), 0, 8);
    }

    /**
     * @param  array{nama: string, nama_singkat?: ?string, deskripsi?: ?string, pendaftaran?: ?bool}  $teks
     * @param  array<string, UploadedFile|null>  $unggahan  keyed by self::BERKAS slugs
     */
    public function simpan(array $teks, array $unggahan, User $oleh): void
    {
        Pengaturan::simpan('identitas.nama', $teks['nama'], $oleh);
        Pengaturan::simpan('identitas.nama_singkat', $teks['nama_singkat'] ?? null, $oleh);
        Pengaturan::simpan('landing.deskripsi', $teks['deskripsi'] ?? null, $oleh);
        if (isset($teks['pendaftaran'])) {
            Pengaturan::simpan('akun.pendaftaran', $teks['pendaftaran'] ? '1' : '0', $oleh);
        }

        foreach (array_filter($unggahan) as $jenis => $berkas) {
            $lama = $this->berkas($jenis);
            Pengaturan::simpan(self::BERKAS[$jenis], $berkas->store('identitas'), $oleh);
            if ($lama !== null) {
                Storage::delete($lama);
            }
        }

        $this->lupakan();
    }

    /** Remove an uploaded image so the default is used again. */
    public function hapusBerkas(string $jenis, User $oleh): void
    {
        $lama = $this->berkas($jenis);
        if ($lama === null) {
            return;
        }

        Pengaturan::simpan(self::BERKAS[$jenis], null, $oleh);
        Storage::delete($lama);
        $this->lupakan();
    }

    /** Make the saved name the app name for mail and anything else reading config. */
    public function terapkanKeConfig(): void
    {
        config(['app.nama_bawaan' => config('app.nama_bawaan', config('app.name'))]);
        config(['app.name' => $this->nama()]);
    }

    private function lupakan(): void
    {
        Cache::forget(self::CACHE);
        $this->nilai = null;
        $this->terapkanKeConfig();
    }

    private function ambil(string $kunci): ?string
    {
        if ($this->nilai === null) {
            try {
                $this->nilai = Cache::rememberForever(self::CACHE, fn () => Pengaturan::whereIn('kunci', self::KUNCI)
                    ->pluck('nilai', 'kunci')->all());
            } catch (QueryException) {
                // Before the first migration there is no table yet: use the defaults.
                return null;
            }
        }

        $nilai = trim((string) ($this->nilai[$kunci] ?? ''));

        return $nilai === '' ? null : $nilai;
    }
}
