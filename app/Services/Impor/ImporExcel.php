<?php

namespace App\Services\Impor;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\XLSX\Reader;

/**
 * An import from the first sheet of an Excel workbook.
 */
abstract class ImporExcel extends Impor
{
    public function jenisBerkas(): array
    {
        return ['xlsx'];
    }

    /** An xlsx file is a zip archive; some systems report it only as that. */
    public function aturanBerkas(): array
    {
        return ['extensions:xlsx', 'mimetypes:application/zip,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    }

    /**
     * Reads the first sheet; the header row gives the keys.
     */
    public function baca(UploadedFile $berkas): array
    {
        $reader = new Reader;
        try {
            $reader->open($berkas->getRealPath());
        } catch (OpenSpoutException) {
            throw ValidationException::withMessages(['berkas' => 'Berkas bukan file Excel yang bisa dibaca. Gunakan templat.']);
        }
        $hasil = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $kepala = null;
                foreach ($sheet->getRowIterator() as $row) {
                    $sel = array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $row->toArray());
                    if ($kepala === null) {
                        $kepala = array_map('strtolower', $sel);

                        continue;
                    }
                    if (implode('', $sel) === '') {
                        continue;
                    }
                    $hasil[] = array_combine($kepala, array_pad(array_slice($sel, 0, count($kepala)), count($kepala), ''));
                }
                break;
            }
        } finally {
            $reader->close();
        }

        return $hasil;
    }
}
