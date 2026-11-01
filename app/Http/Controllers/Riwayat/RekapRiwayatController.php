<?php

namespace App\Http\Controllers\Riwayat;

use App\Http\Controllers\Controller;
use App\Services\AlurRiwayat;
use App\Services\RekapRiwayat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The research and community service summary for LPPM, operators and the
 * institution's heads (every lecturer) and unit heads (their unit's).
 */
class RekapRiwayatController extends Controller
{
    /** Years shown when none are chosen: this year and the four before it. */
    private const RENTANG_BAWAAN = 5;

    public function index(Request $request, string $jenis, RekapRiwayat $rekap): View
    {
        $filter = $request->validate([
            'tahun_dari' => ['nullable', 'integer', 'between:1950,2100'],
            'tahun_sampai' => ['nullable', 'integer', 'between:1950,2100'],
            'prodi_id' => ['nullable', 'integer'],
            'sumber_dana' => ['nullable', Rule::in(array_keys(RekapRiwayat::sumberDana()))],
        ]);
        $filter['tahun_sampai'] = (int) ($filter['tahun_sampai'] ?? now()->year);
        $filter['tahun_dari'] = (int) ($filter['tahun_dari'] ?? $filter['tahun_sampai'] - self::RENTANG_BAWAAN + 1);
        if ($filter['tahun_dari'] > $filter['tahun_sampai']) {
            [$filter['tahun_dari'], $filter['tahun_sampai']] = [$filter['tahun_sampai'], $filter['tahun_dari']];
        }
        $user = $request->user();

        return view('riwayat.rekap', [
            'jenis' => $jenis,
            'filter' => $filter,
            'prodi' => $rekap->prodi($user),
            'lihatSemua' => RekapRiwayat::bolehLihatSemua($user),
        ] + $rekap->hitung($user, AlurRiwayat::JENIS[$jenis], $filter));
    }
}
