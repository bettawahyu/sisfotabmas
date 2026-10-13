<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Why the operator sent a final report back, shown to the lead.
     */
    public function up(): void
    {
        Schema::table('laporan', function (Blueprint $table) {
            $table->text('catatan_verifikasi')->nullable()->after('diverifikasi_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan', function (Blueprint $table) {
            $table->dropColumn('catatan_verifikasi');
        });
    }
};
