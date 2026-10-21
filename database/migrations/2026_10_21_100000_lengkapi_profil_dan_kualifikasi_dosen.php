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
     * Personal details move from the lecturer to the account, so every user
     * keeps them on the profile page; the lecturer gains certification,
     * Pekerti, AA and the date they started as a lecturer.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('alamat')->nullable();
            $table->string('no_telp', 30)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('status_pernikahan', 20)->nullable();
        });

        Schema::table('dosen', function (Blueprint $table) {
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
            DB::table('users')->where('id', $dosen->user_id)->update([
                'name' => $dosen->nama,
                'tempat_lahir' => $dosen->tempat_lahir,
                'tgl_lahir' => $dosen->tgl_lahir,
                'jenis_kelamin' => $dosen->jenis_kelamin,
                'no_telp' => $dosen->no_hp,
            ]);
        });

        Schema::table('dosen', function (Blueprint $table) {
            $table->dropColumn(['jenis_kelamin', 'tempat_lahir', 'tgl_lahir', 'no_hp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dosen', function (Blueprint $table) {
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('no_hp', 30)->nullable();
        });

        DB::table('dosen')->whereNotNull('user_id')->orderBy('id')->each(function ($dosen) {
            $user = DB::table('users')->where('id', $dosen->user_id)->first();
            DB::table('dosen')->where('id', $dosen->id)->update([
                'jenis_kelamin' => $user?->jenis_kelamin,
                'tempat_lahir' => $user?->tempat_lahir,
                'tgl_lahir' => $user?->tgl_lahir,
                'no_hp' => $user?->no_telp,
            ]);
        });

        Schema::table('dosen', function (Blueprint $table) {
            $table->dropColumn(['sertifikasi_dosen', 'no_serdos', 'tahun_serdos', 'pekerti', 'tahun_pekerti',
                'applied_approach', 'tahun_applied_approach', 'tmt_dosen']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['alamat', 'no_telp', 'tempat_lahir', 'tgl_lahir', 'jenis_kelamin', 'status_pernikahan']);
        });
    }
};
