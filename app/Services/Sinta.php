<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads a lecturer's metrics from their public SINTA profile page. SINTA has no
 * open API, so this parses the page; the Google Scholar figures come from the
 * same page, so Scholar itself is never visited.
 */
class Sinta
{
    /** Columns of the Scopus / GScholar / WoS table that SINTA shows, in order. */
    private const KOLOM = ['scopus', 'scholar', 'wos'];

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
     * The metrics stored on the lecturer, fetched from SINTA.
     *
     * @return array{skor_sinta: float|null, h_index_scopus: int|null, h_index_scholar: int|null, sitasi_scopus: int|null, sitasi_scholar: int|null}
     *
     * @throws RuntimeException when SINTA cannot be reached or the page is not a profile
     */
    public function ambilMetrik(string $sintaId): array
    {
        try {
            $respons = Http::timeout(20)->withUserAgent(config('app.name').' (sinkron profil SINTA)')->get(self::urlProfil($sintaId));
        } catch (ConnectionException $e) {
            throw new RuntimeException('SINTA tidak dapat dihubungi.', previous: $e);
        }

        if ($respons->status() === 404) {
            throw new RuntimeException('Profil SINTA dengan ID ini tidak ditemukan.');
        }
        if (! $respons->successful()) {
            throw new RuntimeException("SINTA menjawab dengan kode {$respons->status()}.");
        }

        return $this->bacaProfil($respons->body());
    }

    /**
     * @return array{skor_sinta: float|null, h_index_scopus: int|null, h_index_scholar: int|null, sitasi_scopus: int|null, sitasi_scholar: int|null}
     *
     * @throws RuntimeException when the page has neither the score nor the metrics table
     */
    public function bacaProfil(string $html): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        $xpath = new DOMXPath($dom);

        // The first score is "SINTA Score Overall"; the others are 3-year and affiliation scores.
        $skor = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' stat-profile ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' pr-num ')]");

        // Columns are found by their headings, falling back to SINTA's usual order.
        $statTable = "//table[contains(concat(' ', normalize-space(@class), ' '), ' stat-table ')]";
        $kolom = self::KOLOM;
        $judul = $xpath->query($statTable.'//thead//th');
        if ($judul->length > 1) {
            $dariJudul = [];
            foreach ($judul as $i => $th) {
                $teks = strtolower($th->textContent);
                $nama = str_contains($teks, 'scopus') ? 'scopus' : (str_contains($teks, 'scholar') ? 'scholar' : (str_contains($teks, 'wos') ? 'wos' : null));
                if ($nama !== null) {
                    $dariJudul[$i - 1] = $nama;
                }
            }
            if (in_array('scopus', $dariJudul, true) && in_array('scholar', $dariJudul, true)) {
                $kolom = $dariJudul;
            }
        }

        $tabel = [];
        foreach ($xpath->query($statTable.'//tbody/tr') as $tr) {
            $sel = $xpath->query('./td', $tr);
            if ($sel->length < 3) {
                continue;
            }
            $label = strtolower(preg_replace('/[^a-z0-9]/i', '', $sel->item(0)->textContent));
            foreach ($kolom as $i => $nama) {
                $tabel[$label][$nama] = $sel->item($i + 1) ? self::bilangan($sel->item($i + 1)->textContent) : null;
            }
        }

        if ($skor->length === 0 && $tabel === []) {
            throw new RuntimeException('Data profil tidak ditemukan di halaman SINTA. Periksa ID SINTA Anda; bila sudah benar, tampilan SINTA mungkin berubah.');
        }

        $nilaiSkor = $skor->length > 0 ? self::bilangan($skor->item(0)->textContent, desimal: true) : null;

        return [
            'skor_sinta' => $nilaiSkor,
            'h_index_scopus' => $tabel['hindex']['scopus'] ?? null,
            'h_index_scholar' => $tabel['hindex']['scholar'] ?? null,
            'sitasi_scopus' => $tabel['citation']['scopus'] ?? $tabel['citations']['scopus'] ?? null,
            'sitasi_scholar' => $tabel['citation']['scholar'] ?? $tabel['citations']['scholar'] ?? null,
        ];
    }

    /**
     * A number as SINTA prints it, such as "1,234" or "-" for none.
     */
    private static function bilangan(string $teks, bool $desimal = false): int|float|null
    {
        $teks = str_replace(',', '', trim($teks));
        if (! is_numeric($teks)) {
            return null;
        }

        return $desimal ? round((float) $teks, 2) : (int) $teks;
    }
}
