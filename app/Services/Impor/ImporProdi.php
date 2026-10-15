<?php

namespace App\Services\Impor;

use App\Models\Fakultas;
use App\Models\ProgramStudi;
use Illuminate\Support\Facades\DB;

/**
 * Faculties and study programmes, matched by code: a known code is updated,
 * a new one is added. A faculty is created the first time its code appears.
 */
class ImporProdi extends Impor
{
    public function judul(): string
    {
        return 'Impor Fakultas dan Program Studi';
    }

    public function ruteKembali(): string
    {
        return 'sistem.fakultas.index';
    }

    public function kolom(): array
    {
        return ['kode_fakultas', 'nama_fakultas', 'kode_prodi', 'nama_prodi', 'jenjang'];
    }

    public function contoh(): array
    {
        return [
            ['FT', 'Fakultas Teknik', 'TI', 'Teknik Informatika', 'S1'],
            ['FT', 'Fakultas Teknik', 'SI', 'Sistem Informasi', 'S1'],
            ['FEB', 'Fakultas Ekonomi dan Bisnis', 'MJ', 'Manajemen', 'S1'],
        ];
    }

    public function periksa(array $baris): array
    {
        $prodiAda = ProgramStudi::pluck('kode')->flip();
        $diFile = [];

        return array_map(function (array $b) use ($prodiAda, &$diFile) {
            $galat = [];
            foreach ($this->kolom() as $kolom) {
                if (($b[$kolom] ?? '') === '') {
                    $galat[] = "Kolom {$kolom} kosong.";
                }
            }
            if (($b['jenjang'] ?? '') !== '' && ! in_array($b['jenjang'], ProgramStudi::JENJANG, true)) {
                $galat[] = 'Jenjang harus salah satu dari '.implode(', ', ProgramStudi::JENJANG).'.';
            }
            if (strlen($b['kode_prodi'] ?? '') > 20 || strlen($b['kode_fakultas'] ?? '') > 20) {
                $galat[] = 'Kode paling panjang 20 karakter.';
            }
            if (isset($diFile[$b['kode_prodi'] ?? ''])) {
                $galat[] = 'Kode prodi muncul lebih dari sekali di berkas.';
            }
            $diFile[$b['kode_prodi'] ?? ''] = true;

            return ['data' => $b, 'aksi' => $prodiAda->has($b['kode_prodi'] ?? '') ? 'perbarui' : 'baru', 'galat' => $galat];
        }, $baris);
    }

    public function simpan(array $hasil): int
    {
        return DB::transaction(function () use ($hasil) {
            foreach ($hasil as ['data' => $b]) {
                $fakultas = Fakultas::firstOrCreate(['kode' => $b['kode_fakultas']], ['nama' => $b['nama_fakultas']]);
                ProgramStudi::updateOrCreate(['kode' => $b['kode_prodi']], [
                    'fakultas_id' => $fakultas->id,
                    'nama' => $b['nama_prodi'],
                    'jenjang' => $b['jenjang'],
                ]);
            }

            return count($hasil);
        });
    }
}
