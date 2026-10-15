<?php

namespace App\Services\Impor;

use App\Models\RumpunIlmu;
use Illuminate\Support\Facades\DB;

/**
 * The science classification, matched by code. A parent may come from the
 * database or from an earlier row in the same file.
 */
class ImporRumpunIlmu extends Impor
{
    public function judul(): string
    {
        return 'Impor Rumpun Ilmu';
    }

    public function ruteKembali(): string
    {
        return 'sistem.rumpun-ilmu.index';
    }

    public function kolom(): array
    {
        return ['kode', 'nama', 'tingkat', 'kode_induk'];
    }

    public function contoh(): array
    {
        return [
            ['400', 'Ilmu Teknik', 'rumpun', ''],
            ['450', 'Teknik Elektro dan Informatika', 'pohon', '400'],
            ['458', 'Teknik Informatika', 'cabang', '450'],
        ];
    }

    public function periksa(array $baris): array
    {
        // Code => level, starting from the database and growing as rows are read.
        $tingkatKode = RumpunIlmu::pluck('tingkat', 'kode')->all();
        $adaDiDb = $tingkatKode;
        $punyaTurunan = RumpunIlmu::whereHas('turunan')->pluck('kode')->flip();
        $diFile = [];

        return array_map(function (array $b) use (&$tingkatKode, $adaDiDb, $punyaTurunan, &$diFile) {
            $galat = [];
            $tingkat = strtolower($b['tingkat'] ?? '');
            foreach (['kode', 'nama', 'tingkat'] as $kolom) {
                if (($b[$kolom] ?? '') === '') {
                    $galat[] = "Kolom {$kolom} kosong.";
                }
            }
            if ($tingkat !== '' && ! array_key_exists($tingkat, RumpunIlmu::TINGKAT)) {
                $galat[] = 'Tingkat harus rumpun, pohon, atau cabang.';
            }
            if (strlen($b['kode'] ?? '') > 20) {
                $galat[] = 'Kode paling panjang 20 karakter.';
            }
            if (isset($diFile[$b['kode'] ?? ''])) {
                $galat[] = 'Kode muncul lebih dari sekali di berkas.';
            }
            if (isset($adaDiDb[$b['kode'] ?? ''], $punyaTurunan[$b['kode']]) && $adaDiDb[$b['kode']] !== $tingkat) {
                $galat[] = 'Tingkat tidak bisa diubah karena entri ini sudah punya turunan.';
            }

            $perluInduk = RumpunIlmu::TINGKAT_INDUK[$tingkat] ?? null;
            $induk = $b['kode_induk'] ?? '';
            if ($perluInduk && $induk === '') {
                $galat[] = "Tingkat {$tingkat} perlu kode_induk.";
            } elseif ($perluInduk && ($tingkatKode[$induk] ?? null) !== $perluInduk) {
                $galat[] = "Induk {$induk} tidak ditemukan sebagai {$perluInduk} (di basis data atau baris sebelumnya).";
            } elseif (! $perluInduk && $induk !== '') {
                $galat[] = 'Tingkat rumpun tidak berinduk; kosongkan kode_induk.';
            }

            $diFile[$b['kode'] ?? ''] = true;
            if (! $galat) {
                $tingkatKode[$b['kode']] = $tingkat;
            }
            $b['tingkat'] = $tingkat;

            return ['data' => $b, 'aksi' => isset($adaDiDb[$b['kode'] ?? '']) ? 'perbarui' : 'baru', 'galat' => $galat];
        }, $baris);
    }

    public function simpan(array $hasil): int
    {
        return DB::transaction(function () use ($hasil) {
            foreach ($hasil as ['data' => $b]) {
                RumpunIlmu::updateOrCreate(['kode' => $b['kode']], [
                    'nama' => $b['nama'],
                    'tingkat' => $b['tingkat'],
                    'induk_id' => $b['kode_induk'] !== '' ? RumpunIlmu::where('kode', $b['kode_induk'])->value('id') : null,
                ]);
            }

            return count($hasil);
        });
    }
}
