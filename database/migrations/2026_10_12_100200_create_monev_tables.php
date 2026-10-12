<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Funding, review and monitoring after a proposal is submitted. Selection,
     * monev and final review all use penilaian, told apart by tahap.
     */
    public function up(): void
    {
        Schema::create('pencairan_dana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->unsignedTinyInteger('termin_ke');
            $table->unsignedTinyInteger('persentase');
            $table->unsignedBigInteger('jumlah');
            $table->date('tgl_cair')->nullable();
            $table->string('syarat', 30);
            $table->boolean('pajak_lunas')->default(false);
            $table->boolean('ditahan_karena_monev')->default(false);
            $table->foreignId('dicairkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['proposal_id', 'termin_ke']);
        });

        // Rubric per call, so it can change from year to year.
        Schema::create('kriteria_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('periode_hibah')->cascadeOnDelete();
            $table->string('tahap', 20);
            $table->string('nama');
            $table->unsignedTinyInteger('bobot');
            $table->unsignedTinyInteger('skala_maks')->default(7);
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();
            $table->index(['periode_id', 'tahap']);
        });

        Schema::create('laporan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->string('jenis', 20);
            $table->text('ringkasan')->nullable();
            $table->unsignedTinyInteger('persentase_capaian')->nullable();
            $table->unsignedTinyInteger('persentase_anggaran_terpakai')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('diajukan_at')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamps();
            $table->index(['proposal_id', 'jenis']);
        });

        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->foreignId('laporan_id')->nullable()->constrained('laporan')->nullOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('tahap', 20);
            $table->unsignedTinyInteger('urutan_reviewer')->default(1);
            $table->string('status', 20)->default('ditugaskan');
            $table->decimal('nilai_total', 6, 2)->nullable();
            $table->string('rekomendasi', 30)->nullable();
            $table->text('komentar')->nullable();
            $table->unsignedBigInteger('rekomendasi_dana')->nullable();
            $table->timestamp('dinilai_at')->nullable();
            $table->timestamps();
            $table->unique(['proposal_id', 'reviewer_id', 'tahap']);
        });

        Schema::create('nilai_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_id')->constrained('penilaian')->cascadeOnDelete();
            $table->foreignId('kriteria_id')->constrained('kriteria_penilaian')->restrictOnDelete();
            $table->unsignedTinyInteger('skor');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['penilaian_id', 'kriteria_id']);
        });

        Schema::create('logbook', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('kegiatan');
            $table->unsignedTinyInteger('persentase_progres')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('dosen')->nullOnDelete();
            $table->timestamps();
            $table->index(['proposal_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbook');
        Schema::dropIfExists('nilai_penilaian');
        Schema::dropIfExists('penilaian');
        Schema::dropIfExists('laporan');
        Schema::dropIfExists('kriteria_penilaian');
        Schema::dropIfExists('pencairan_dana');
    }
};
