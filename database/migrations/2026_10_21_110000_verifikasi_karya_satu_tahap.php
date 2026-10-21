<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Publications, HKI and patents no longer go through the kaprodi: entries
     * the kaprodi already approved join the queue LPPM or an operator checks.
     * The kaprodi columns stay as a record of past approvals.
     */
    public function up(): void
    {
        foreach (['publikasi', 'hki', 'paten'] as $tabel) {
            DB::table($tabel)->where('status_verifikasi', 'disetujui_kaprodi')->update(['status_verifikasi' => 'menunggu']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['publikasi', 'hki', 'paten'] as $tabel) {
            DB::table($tabel)->where('status_verifikasi', 'menunggu')->whereNotNull('diverifikasi_kaprodi_oleh')
                ->update(['status_verifikasi' => 'disetujui_kaprodi']);
        }
    }
};
