<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The lecturer profile gains address, marital status, certification,
     * Pekerti, AA and the date they started as a lecturer.
     */
    public function up(): void
    {
        Schema::table('dosen', function (Blueprint $table) {
            $table->text('alamat')->nullable();
            $table->string('status_pernikahan', 20)->nullable();
            $table->boolean('sertifikasi_dosen')->default(false);
            $table->string('no_serdos', 30)->nullable();
            $table->unsignedSmallInteger('tahun_serdos')->nullable();
            $table->boolean('pekerti')->default(false);
            $table->unsignedSmallInteger('tahun_pekerti')->nullable();
            $table->boolean('applied_approach')->default(false);
            $table->unsignedSmallInteger('tahun_applied_approach')->nullable();
            $table->date('tmt_dosen')->nullable();
        });

        // The lecturer's name is the one kept; the account follows it.
        DB::table('dosen')->whereNotNull('user_id')->orderBy('id')->each(function ($dosen) {
            DB::table('users')->where('id', $dosen->user_id)->update(['name' => $dosen->nama]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dosen', function (Blueprint $table) {
            $table->dropColumn(['alamat', 'status_pernikahan', 'sertifikasi_dosen', 'no_serdos', 'tahun_serdos',
                'pekerti', 'tahun_pekerti', 'applied_approach', 'tahun_applied_approach', 'tmt_dosen']);
        });
    }
};
