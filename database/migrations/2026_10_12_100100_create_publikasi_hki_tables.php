<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Publications, HKI and patents. HKI and patents have separate tables because
     * their registration data differ. Authors, creators and inventors may be
     * people outside the campus.
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
            $this->verifikasi($table);
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

        // Hak cipta, merek, desain industri, rahasia dagang and the like.
        Schema::create('hki', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 30);
            $table->string('jenis_ciptaan', 60)->nullable();
            $table->string('judul', 500);
            $table->text('uraian')->nullable();
            $table->string('pemegang_hak')->nullable();
            $table->string('no_permohonan', 50)->nullable();
            $table->date('tgl_permohonan')->nullable();
            $table->string('no_pencatatan', 50)->nullable();
            $table->date('tgl_pencatatan')->nullable();
            $table->date('masa_berlaku_sampai')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('url_pdki')->nullable();
            $this->verifikasi($table);
            $table->index(['jenis', 'status']);
        });

        Schema::create('hki_pencipta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hki_id')->constrained('hki')->cascadeOnDelete();
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('nama');
            $table->string('afiliasi')->nullable();
            $table->unsignedTinyInteger('urutan');
            $table->timestamps();
            $table->unique(['hki_id', 'urutan']);
        });

        // Paten and paten sederhana, with the stages of the DJKI examination.
        Schema::create('paten', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->string('judul', 500);
            $table->text('abstrak')->nullable();
            $table->string('pemegang_paten')->nullable();
            $table->string('konsultan_ki')->nullable();
            $table->string('no_permohonan', 50)->nullable();
            $table->date('tgl_penerimaan')->nullable();
            $table->date('tgl_publikasi')->nullable();
            $table->string('klasifikasi_ipc', 60)->nullable();
            $table->unsignedSmallInteger('jumlah_klaim')->nullable();
            $table->string('no_paten', 50)->nullable();
            $table->date('tgl_pemberian')->nullable();
            $table->date('masa_berlaku_sampai')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('url_pdki')->nullable();
            $this->verifikasi($table);
            $table->index(['jenis', 'status']);
        });

        Schema::create('paten_inventor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paten_id')->constrained('paten')->cascadeOnDelete();
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('nama');
            $table->string('afiliasi')->nullable();
            $table->unsignedTinyInteger('urutan');
            $table->timestamps();
            $table->unique(['paten_id', 'urutan']);
        });

        // The proposal module left these unconstrained until the tables existed.
        Schema::table('luaran', function (Blueprint $table) {
            $table->renameColumn('ki_id', 'hki_id');
        });

        Schema::table('luaran', function (Blueprint $table) {
            $table->foreignId('paten_id')->nullable()->after('hki_id')->constrained('paten')->nullOnDelete();
            $table->foreign('publikasi_id')->references('id')->on('publikasi')->nullOnDelete();
            $table->foreign('hki_id')->references('id')->on('hki')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('luaran', function (Blueprint $table) {
            $table->dropForeign(['publikasi_id']);
            $table->dropForeign(['hki_id']);
            $table->dropConstrainedForeignId('paten_id');
        });

        Schema::table('luaran', function (Blueprint $table) {
            $table->renameColumn('hki_id', 'ki_id');
        });

        Schema::dropIfExists('paten_inventor');
        Schema::dropIfExists('paten');
        Schema::dropIfExists('hki_pencipta');
        Schema::dropIfExists('hki');
        Schema::dropIfExists('publikasi_penulis');
        Schema::dropIfExists('publikasi');
    }

    /**
     * Two-step verification: the kaprodi checks first, then LPPM makes it valid.
     */
    private function verifikasi(Blueprint $table): void
    {
        $table->foreignId('diinput_oleh')->nullable()->constrained('users')->nullOnDelete();
        $table->string('status_verifikasi', 20)->default('menunggu');
        $table->foreignId('diverifikasi_kaprodi_oleh')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('diverifikasi_kaprodi_at')->nullable();
        $table->foreignId('diverifikasi_lppm_oleh')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('diverifikasi_lppm_at')->nullable();
        $table->text('catatan_verifikasi')->nullable();
        $table->timestamps();
        $table->softDeletes();
    }
};
