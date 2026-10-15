<?php

namespace App\Services;

use App\Enums\TipeAnggota;
use App\Models\Dosen;
use App\Models\Proposal;
use App\Models\SkemaHibah;

/**
 * The requirements a scheme sets on its lead, its team and its outputs. The lead's
 * are checked before a proposal is started; the team's and outputs' before it is sent.
 */
class SyaratSkema
{
    /**
     * The scheme's requirements in plain words, for the call listing.
     *
     * @return list<string>
     */
    public function ringkasan(SkemaHibah $skema): array
    {
        $syarat = [];
        if ($skema->jabatan_min) {
            $syarat[] = "Ketua minimal {$skema->jabatan_min->label()}";
        }
        if ($skema->pendidikan_min) {
            $syarat[] = "Ketua berpendidikan minimal {$skema->pendidikan_min}";
        }
        $min = $skema->anggota_dosen_min;
        $maks = $skema->anggota_dosen_maks;
        if ($min !== null && $maks !== null) {
            $syarat[] = $min === $maks ? "{$min} anggota dosen" : "{$min} sampai {$maks} anggota dosen";
        } elseif ($min !== null) {
            $syarat[] = "Minimal {$min} anggota dosen";
        } elseif ($maks !== null) {
            $syarat[] = "Maksimal {$maks} anggota dosen";
        }
        if ($skema->anggota_mahasiswa_min) {
            $syarat[] = "Minimal {$skema->anggota_mahasiswa_min} anggota mahasiswa";
        }
        foreach ($skema->jenisLuaranWajib as $jenis) {
            $syarat[] = "Luaran wajib {$jenis->pivot->jumlah_min} {$jenis->nama}";
        }

        return $syarat;
    }

    /**
     * What keeps this lecturer from leading a proposal under the scheme.
     *
     * @return list<string>
     */
    public function kekuranganKetua(?Dosen $dosen, SkemaHibah $skema): array
    {
        $masalah = [];
        if ($skema->jabatan_min && ! $dosen?->jabatan_fungsional?->setidaknya($skema->jabatan_min)) {
            $masalah[] = "Skema ini mensyaratkan ketua minimal {$skema->jabatan_min->label()}; jabatan fungsional Anda "
                .($dosen?->jabatan_fungsional ? $dosen->jabatan_fungsional->label() : 'belum diisi').'.';
        }
        if ($skema->pendidikan_min && ! $dosen?->pendidikanSetidaknya($skema->pendidikan_min)) {
            $masalah[] = "Skema ini mensyaratkan ketua berpendidikan minimal {$skema->pendidikan_min}; pendidikan terakhir Anda "
                .($dosen?->pendidikan_terakhir ?: 'belum diisi').'.';
        }

        return $masalah;
    }

    /**
     * What the team and the outputs still lack.
     *
     * @return list<string>
     */
    public function kekuranganProposal(Proposal $proposal): array
    {
        $skema = $proposal->periode->skema;
        $masalah = [];

        $dosen = $proposal->anggota->where('tipe', TipeAnggota::Dosen)->whereNull('ditolak_at')->count();
        if ($skema->anggota_dosen_min !== null && $dosen < $skema->anggota_dosen_min) {
            $masalah[] = "Skema ini mensyaratkan minimal {$skema->anggota_dosen_min} anggota dosen (sekarang {$dosen}).";
        }
        if ($skema->anggota_dosen_maks !== null && $dosen > $skema->anggota_dosen_maks) {
            $masalah[] = "Skema ini membatasi anggota dosen paling banyak {$skema->anggota_dosen_maks} (sekarang {$dosen}).";
        }
        $mahasiswa = $proposal->anggota->where('tipe', TipeAnggota::Mahasiswa)->count();
        if ($skema->anggota_mahasiswa_min && $mahasiswa < $skema->anggota_mahasiswa_min) {
            $masalah[] = "Skema ini mensyaratkan minimal {$skema->anggota_mahasiswa_min} anggota mahasiswa (sekarang {$mahasiswa}).";
        }

        $jumlahPerJenis = $proposal->luaran->countBy('jenis');
        foreach ($skema->jenisLuaranWajib as $jenis) {
            $ada = $jumlahPerJenis->get($jenis->kode, 0);
            if ($ada < $jenis->pivot->jumlah_min) {
                $masalah[] = "Luaran wajib skema: {$jenis->pivot->jumlah_min} {$jenis->nama} (sekarang {$ada}).";
            }
        }

        return $masalah;
    }
}
