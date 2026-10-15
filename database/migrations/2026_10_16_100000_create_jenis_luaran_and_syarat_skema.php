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
     * Output types move from the code into reference data the system admin keeps,
     * a scheme lists the output types it requires, and a scheme can set who may
     * lead and how large the team must be.
     */
    public function up(): void
    {
        Schema::create('jenis_luaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('nama');
            // semua, penelitian or pkm.
            $table->string('berlaku_untuk', 20)->default('semua');
            // publikasi, hki or paten: the page an achieved output can be linked to.
            $table->string('tautan_karya', 20)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Same codes the luaran table already stores, so existing outputs keep their type.
        $jenis = [
            ['artikel_jurnal', 'Artikel jurnal', 'publikasi'],
            ['prosiding', 'Prosiding', 'publikasi'],
            ['hki', 'HKI', 'hki'],
            ['paten', 'Paten', 'paten'],
            ['buku', 'Buku', 'publikasi'],
            ['teknologi_tepat_guna', 'Teknologi tepat guna', null],
            ['video_kegiatan', 'Video kegiatan', null],
            ['publikasi_media', 'Publikasi media', null],
            ['testimoni_mitra', 'Testimoni mitra', null],
        ];
        DB::table('jenis_luaran')->insert(collect($jenis)->map(fn ($j, $i) => [
            'kode' => $j[0], 'nama' => $j[1], 'berlaku_untuk' => 'semua', 'tautan_karya' => $j[2],
            'urutan' => ($i + 1) * 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ])->all());

        Schema::create('skema_luaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skema_id')->constrained('skema_hibah')->cascadeOnDelete();
            $table->foreignId('jenis_luaran_id')->constrained('jenis_luaran')->restrictOnDelete();
            $table->unsignedTinyInteger('jumlah_min')->default(1);
            $table->timestamps();
            $table->unique(['skema_id', 'jenis_luaran_id']);
        });

        Schema::table('skema_hibah', function (Blueprint $table) {
            $table->string('jabatan_min', 20)->nullable();
            $table->string('pendidikan_min', 10)->nullable();
            $table->unsignedTinyInteger('anggota_dosen_min')->nullable();
            $table->unsignedTinyInteger('anggota_dosen_maks')->nullable();
            $table->unsignedTinyInteger('anggota_mahasiswa_min')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skema_hibah', function (Blueprint $table) {
            $table->dropColumn(['jabatan_min', 'pendidikan_min', 'anggota_dosen_min', 'anggota_dosen_maks', 'anggota_mahasiswa_min']);
        });

        Schema::dropIfExists('skema_luaran');
        Schema::dropIfExists('jenis_luaran');
    }
};
