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
        Schema::create('bidang_fokus', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_kegiatan', 20);
            $table->string('kode')->unique();
            $table->string('nama');
            $table->foreignId('induk_id')->nullable()->constrained('bidang_fokus')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // A scheme only holds defaults; each year's call copies and may change them.
        Schema::create('skema_hibah', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('jenis_kegiatan', 20);
            $table->string('sumber_dana', 20)->default('internal');
            $table->boolean('is_multitahun')->default(false);
            $table->unsignedTinyInteger('lama_maks_tahun')->default(1);
            $table->unsignedBigInteger('dana_maksimal_default');
            $table->unsignedTinyInteger('tkt_min')->nullable();
            $table->boolean('honor_diizinkan')->default(false);
            $table->unsignedTinyInteger('batas_honor_persen')->nullable();
            $table->text('luaran_wajib')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('periode_hibah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skema_id')->constrained('skema_hibah')->restrictOnDelete();
            $table->unsignedSmallInteger('tahun_anggaran');
            $table->string('nama');
            $table->date('tgl_buka');
            $table->date('tgl_tutup');
            $table->date('tgl_seleksi_selesai')->nullable();
            $table->date('tgl_pengumuman')->nullable();
            $table->date('tgl_laporan_kemajuan')->nullable();
            $table->date('tgl_monev')->nullable();
            $table->date('tgl_laporan_akhir')->nullable();
            $table->unsignedInteger('kuota')->nullable();
            $table->unsignedBigInteger('dana_maksimal');
            $table->boolean('honor_diizinkan')->default(false);
            $table->unsignedTinyInteger('batas_honor_persen')->nullable();
            $table->unsignedTinyInteger('termin_pertama_persen')->default(80);
            $table->unsignedTinyInteger('maks_sebagai_ketua')->default(1);
            $table->unsignedTinyInteger('maks_sebagai_anggota')->default(2);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_hibah');
        Schema::dropIfExists('skema_hibah');
        Schema::dropIfExists('bidang_fokus');
    }
};
