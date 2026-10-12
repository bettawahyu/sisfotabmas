<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Publications and intellectual property (HKI and patents share one table,
     * told apart by jenis). Authors and inventors may be people outside the campus.
     */
    public function up(): void
    {
        Schema::create('publikasi', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 30);
            $table->string('judul', 500);
            $table->text('abstrak')->nullable();
            $table->string('nama_wadah')->nullable();
            $table->string('issn_isbn', 30)->nullable();
            $table->string('volume', 20)->nullable();
            $table->string('nomor', 20)->nullable();
            $table->string('halaman', 30)->nullable();
            $table->unsignedSmallInteger('tahun');
            $table->date('tgl_terbit')->nullable();
            $table->string('doi')->nullable()->unique();
            $table->string('url')->nullable();
            $table->string('peringkat', 20)->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('diinput_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_verifikasi', 20)->default('menunggu');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tahun', 'jenis']);
        });

        Schema::create('publikasi_penulis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publikasi_id')->constrained('publikasi')->cascadeOnDelete();
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('nama_penulis');
            $table->string('afiliasi')->nullable();
            $table->unsignedTinyInteger('urutan');
            $table->boolean('is_korespondensi')->default(false);
            $table->timestamps();
            $table->unique(['publikasi_id', 'urutan']);
        });

        Schema::create('kekayaan_intelektual', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 30);
            $table->string('judul', 500);
            $table->text('deskripsi')->nullable();
            $table->string('pemegang_hak')->nullable();
            $table->string('no_permohonan', 50)->nullable();
            $table->date('tgl_permohonan')->nullable();
            $table->string('no_pencatatan', 50)->nullable();
            $table->date('tgl_terbit')->nullable();
            $table->date('masa_berlaku_sampai')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('url_pdki')->nullable();
            $table->foreignId('diinput_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_verifikasi', 20)->default('menunggu');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['jenis', 'status']);
        });

        Schema::create('ki_inventor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ki_id')->constrained('kekayaan_intelektual')->cascadeOnDelete();
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('nama');
            $table->string('afiliasi')->nullable();
            $table->unsignedTinyInteger('urutan');
            $table->timestamps();
            $table->unique(['ki_id', 'urutan']);
        });

        // The proposal module left these unconstrained until the tables existed.
        Schema::table('luaran', function (Blueprint $table) {
            $table->foreign('publikasi_id')->references('id')->on('publikasi')->nullOnDelete();
            $table->foreign('ki_id')->references('id')->on('kekayaan_intelektual')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('luaran', function (Blueprint $table) {
            $table->dropForeign(['publikasi_id']);
            $table->dropForeign(['ki_id']);
        });

        Schema::dropIfExists('ki_inventor');
        Schema::dropIfExists('kekayaan_intelektual');
        Schema::dropIfExists('publikasi_penulis');
        Schema::dropIfExists('publikasi');
    }
};
