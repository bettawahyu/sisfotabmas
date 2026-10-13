<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set once the announcement has been sent to its readers, so a scheduled
        // announcement is sent exactly once when it goes live.
        Schema::table('pengumuman', function (Blueprint $table) {
            $table->timestamp('notifikasi_terkirim_at')->nullable()->after('berakhir_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengumuman', function (Blueprint $table) {
            $table->dropColumn('notifikasi_terkirim_at');
        });
    }
};
