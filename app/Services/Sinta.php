<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * A lecturer's metrics from the official SINTA API (manual V3): log in with the
 * campus account, then read the author's profile by SINTA ID. The API is only
 * used once the SINTA team has issued the account and registered the server's
 * IP; until SINTA_API_* is filled in, syncing stays off.
 */
class Sinta
{
    private const CACHE_TOKEN = 'sinta-api-token';

    public static function urlProfil(string $sintaId): string
    {
        return rtrim(config('services.sinta.url'), '/').'/authors/profile/'.$sintaId;
    }

    /**
     * The profile ID, from a bare ID or a pasted profile link.
     */
    public static function idDari(?string $isian): ?string
    {
        $isian = trim((string) $isian);
        if (preg_match('#/authors/profile/(\d+)#', $isian, $m)) {
            return $m[1];
        }

        return $isian === '' ? null : $isian;
    }

    /**
     * Whether the SINTA team's account is filled in, so syncing can be offered.
     */
    public static function aktif(): bool
    {
        $api = config('services.sinta.api');

        return filled($api['uniq']) && filled($api['username']) && filled($api['password']);
    }

    /**
     * The metrics stored on the lecturer, read from the author's SINTA profile.
     *
     * @return array{skor_sinta: float|null, h_index_scopus: int|null, h_index_scholar: int|null, sitasi_scopus: int|null, sitasi_scholar: int|null}
     *
     * @throws RuntimeException when the API is off, cannot be reached or does not know the author
     */
    public function ambilMetrik(string $sintaId): array
    {
        if (! self::aktif()) {
            throw new RuntimeException('Koneksi API SINTA belum diaktifkan.');
        }

        $profil = $this->kirim("/author/profile/id/{$sintaId}");
        $metrik = self::petakan($profil->json() ?? []);

        if (array_filter($metrik, fn ($n) => $n !== null) === []) {
            // The manual shows no response fields, so log what came back to fix the mapping.
            Log::warning('Jawaban API SINTA tanpa metrik yang dikenali', ['sinta_id' => $sintaId, 'kunci' => array_keys(Arr::dot($profil->json() ?? []))]);

            throw new RuntimeException('Jawaban API SINTA tidak berisi skor atau h-index yang dikenali.');
        }

        return $metrik;
    }

    /**
     * Finds the figures in an author response. The manual names the endpoints
     * but not the fields, so keys are matched by what they contain, such as
     * "scopus" with "h index", whatever their exact spelling or nesting.
     *
     * @param  array<mixed>  $jawaban
     * @return array{skor_sinta: float|null, h_index_scopus: int|null, h_index_scholar: int|null, sitasi_scopus: int|null, sitasi_scholar: int|null}
     */
    public static function petakan(array $jawaban): array
    {
        $nilai = [];
        foreach (Arr::dot($jawaban) as $kunci => $isi) {
            if (is_numeric($isi)) {
                $nilai[strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $kunci))] = $isi;
            }
        }

        $cari = function (array $harus, array $salahSatu = [], array $bukan = []) use ($nilai) {
            foreach ($nilai as $kunci => $isi) {
                $cocok = collect($harus)->every(fn ($k) => str_contains($kunci, $k))
                    && ($salahSatu === [] || collect($salahSatu)->contains(fn ($k) => str_contains($kunci, $k)))
                    && ! collect($bukan)->contains(fn ($k) => str_contains($kunci, $k));
                if ($cocok) {
                    return $isi;
                }
            }

            return null;
        };

        $scholar = ['google', 'scholar', 'gs'];
        $skor = $cari(['score'], ['overall', 'all', 'total'], ['3y', '3yr', '3year', 'affil'])
            ?? $cari(['sinta', 'score'], [], ['3y', '3yr', '3year', 'affil']);

        return [
            'skor_sinta' => $skor === null ? null : round((float) $skor, 2),
            'h_index_scopus' => self::bulat($cari(['scopus', 'hindex'])),
            'h_index_scholar' => self::bulat($cari(['hindex'], $scholar, ['scopus', 'wos'])),
            'sitasi_scopus' => self::bulat($cari(['scopus'], ['citation', 'cited', 'sitasi'], ['document', 'doc'])),
            'sitasi_scholar' => self::bulat($cari([], array_merge(...array_map(fn ($s) => [$s.'citation', $s.'cited', $s.'sitasi', 'citation'.$s, 'cited'.$s], $scholar)), ['document', 'doc'])),
        ];
    }

    private static function bulat(mixed $isi): ?int
    {
        return $isi === null ? null : (int) $isi;
    }

    /**
     * POSTs to /v3/{env}/{uniq}{path} with the token, logging in again once if it expired.
     */
    private function kirim(string $path, array $isi = []): Response
    {
        $api = config('services.sinta.api');
        $url = rtrim($api['url'], '/')."/v3/{$api['env']}/{$api['uniq']}{$path}";

        for ($ulang = 0; $ulang < 2; $ulang++) {
            try {
                $respons = Http::timeout(20)->acceptJson()->withToken($this->token($ulang > 0))->post($url, $isi);
            } catch (ConnectionException $e) {
                throw new RuntimeException('API SINTA tidak dapat dihubungi.', previous: $e);
            }
            if ($respons->status() !== 401) {
                break;
            }
        }

        return match (true) {
            $respons->successful() => $respons,
            $respons->status() === 404 => throw new RuntimeException('Profil SINTA dengan ID ini tidak ditemukan.'),
            $respons->status() === 403 => throw new RuntimeException('API SINTA menolak akses; pastikan IP server sudah didaftarkan ke tim SINTA.'),
            $respons->status() === 429 => throw new RuntimeException('Kuota harian API SINTA sudah habis.'),
            default => throw new RuntimeException("API SINTA menjawab dengan kode {$respons->status()}."),
        };
    }

    private function token(bool $baru = false): string
    {
        if ($baru) {
            Cache::forget(self::CACHE_TOKEN);
        }

        return Cache::remember(self::CACHE_TOKEN, now()->addMinutes(50), function () {
            $api = config('services.sinta.api');
            try {
                $respons = Http::timeout(20)->acceptJson()->post(rtrim($api['url'], '/').'/consumer/login', [
                    'username' => $api['username'],
                    'password' => $api['password'],
                ]);
            } catch (ConnectionException $e) {
                throw new RuntimeException('API SINTA tidak dapat dihubungi.', previous: $e);
            }

            $token = $respons->successful() ? self::tokenDari($respons->json() ?? []) : null;
            if ($token === null) {
                throw new RuntimeException('Login ke API SINTA gagal; periksa akun API di pengaturan server.');
            }

            return $token;
        });
    }

    /**
     * The token from the login answer, wherever it sits ("token", "data.token", "access_token", ...).
     */
    private static function tokenDari(array $jawaban): ?string
    {
        foreach (Arr::dot($jawaban) as $kunci => $isi) {
            if (is_string($isi) && $isi !== '' && str_contains(strtolower((string) $kunci), 'token')) {
                return $isi;
            }
        }

        return null;
    }
}
