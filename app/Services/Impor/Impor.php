<?php

namespace App\Services\Impor;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * An import from a spreadsheet file: read the file, check every row and show
 * the result before anything is saved. Reference data comes as CSV.
 */
abstract class Impor
{
    public const MAKS_BARIS = 2000;

    abstract public function judul(): string;

    abstract public function ruteKembali(): string;

    /**
     * Column headers, in the order the template lists them.
     *
     * @return list<string>
     */
    abstract public function kolom(): array;

    /**
     * Example rows for the downloadable template.
     *
     * @return list<list<string>>
     */
    abstract public function contoh(): array;

    /**
     * Checks each row against the database and the rows before it.
     *
     * @param  list<array<string, string>>  $baris
     * @return list<array{data: array<string, string>, aksi: string, galat: list<string>}>
     */
    abstract public function periksa(array $baris): array;

    /**
     * Saves rows already checked by periksa(). Returns how many were written.
     *
     * @param  list<array{data: array<string, string>, aksi: string, galat: list<string>}>  $hasil
     */
    abstract public function simpan(array $hasil): int;

    public static function untuk(string $jenis): ?self
    {
        return match ($jenis) {
            'prodi' => new ImporProdi,
            'rumpun-ilmu' => new ImporRumpunIlmu,
            'pengguna' => new ImporPengguna,
            default => null,
        };
    }

    /** The most rows one file may hold. */
    public function maksBaris(): int
    {
        return self::MAKS_BARIS;
    }

    /**
     * Extensions the upload accepts.
     *
     * @return list<string>
     */
    public function jenisBerkas(): array
    {
        return ['csv', 'txt'];
    }

    /**
     * Validation rules for the uploaded file's type.
     *
     * @return list<string>
     */
    public function aturanBerkas(): array
    {
        return ['mimes:'.implode(',', $this->jenisBerkas())];
    }

    public function petunjuk(): string
    {
        return 'Kolom yang dibutuhkan: '.implode(', ', $this->kolom()).'. Pemisah koma atau titik koma. '
            .'Baris dengan kode yang sudah ada akan diperbarui; kode baru akan ditambahkan.';
    }

    /**
     * The template: the header row and the example rows as CSV.
     */
    public function unduhTemplat(string $jenis): Response
    {
        return response()->streamDownload(function () {
            $keluar = fopen('php://output', 'w');
            fputcsv($keluar, $this->kolom(), ',', '"', '');
            foreach ($this->contoh() as $baris) {
                fputcsv($keluar, $baris, ',', '"', '');
            }
            fclose($keluar);
        }, "templat-{$jenis}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * Reads the file into rows keyed by the lower-cased header. Accepts comma or
     * semicolon separators, since spreadsheets in Indonesian locales save with semicolons.
     *
     * @return list<array<string, string>>
     */
    public function baca(UploadedFile $berkas): array
    {
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($berkas->getRealPath()));
        $baris = preg_split('/\r\n|\n|\r/', trim($isi));
        $pemisah = substr_count($baris[0] ?? '', ';') > substr_count($baris[0] ?? '', ',') ? ';' : ',';

        $kepala = array_map(fn ($k) => strtolower(trim($k)), str_getcsv(array_shift($baris) ?? '', $pemisah, '"', ''));
        $hasil = [];
        foreach ($baris as $teks) {
            if (trim($teks) === '') {
                continue;
            }
            $sel = array_map('trim', str_getcsv($teks, $pemisah, '"', ''));
            $hasil[] = array_combine($kepala, array_pad(array_slice($sel, 0, count($kepala)), count($kepala), ''));
        }

        return $hasil;
    }

    /**
     * @return list<string> Headers the template needs that the file lacks.
     */
    public function kolomHilang(array $baris): array
    {
        return array_values(array_diff($this->kolom(), array_keys($baris[0] ?? [])));
    }
}
