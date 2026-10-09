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
        Schema::create('proposal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('periode_hibah')->restrictOnDelete();
            $table->foreignId('ketua_id')->constrained('dosen')->restrictOnDelete();
            $table->foreignId('bidang_fokus_id')->nullable()->constrained('bidang_fokus')->nullOnDelete();
            $table->string('judul', 500);
            $table->text('ringkasan')->nullable();
            $table->string('kata_kunci')->nullable();
            $table->string('rumpun_ilmu')->nullable();
            $table->unsignedTinyInteger('tkt_awal')->nullable();
            $table->unsignedTinyInteger('tkt_target')->nullable();
            $table->unsignedTinyInteger('lama_tahun')->default(1);
            $table->unsignedTinyInteger('tahun_ke')->default(1);
            $table->foreignId('proposal_induk_id')->nullable()->constrained('proposal')->nullOnDelete();
            $table->unsignedBigInteger('dana_diusulkan')->default(0);
            $table->unsignedBigInteger('dana_disetujui')->nullable();
            $table->string('status', 20)->default('draft');
            // Set when the lead sends it for endorsement; status stays draft until endorsed.
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamp('diajukan_at')->nullable();
            $table->timestamp('batas_perbaikan_at')->nullable();
            $table->foreignId('disahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disahkan_at')->nullable();
            $table->decimal('nilai_akhir', 6, 2)->nullable();
            $table->unsignedInteger('peringkat')->nullable();
            $table->string('no_sk')->nullable();
            $table->date('tgl_sk')->nullable();
            $table->string('no_kontrak')->nullable();
            $table->date('tgl_kontrak')->nullable();
            $table->date('tgl_ttd_pengusul')->nullable();
            $table->date('tgl_ttd_lppm')->nullable();
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_selesai')->nullable();
            $table->string('id_eksternal')->nullable();
            $table->string('no_kontrak_eksternal')->nullable();
            $table->string('pemberi_dana')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['periode_id', 'status']);
        });

        Schema::create('proposal_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->string('tipe', 20);
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('nama');
            $table->string('nim', 30)->nullable();
            $table->string('institusi')->nullable();
            $table->string('peran')->nullable();
            $table->text('uraian_tugas')->nullable();
            $table->string('token_undangan', 64)->nullable()->unique();
            $table->timestamp('dikonfirmasi_at')->nullable();
            $table->timestamp('ditolak_at')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_mitra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->string('nama_mitra');
            $table->string('jenis_mitra', 40);
            $table->string('alamat')->nullable();
            $table->string('kontak')->nullable();
            $table->unsignedBigInteger('dana_pendamping')->default(0);
            $table->timestamps();
        });

        Schema::create('rab_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->unsignedTinyInteger('tahun_ke')->default(1);
            $table->string('kategori', 30);
            $table->string('uraian');
            $table->decimal('volume', 10, 2);
            $table->string('satuan', 30);
            $table->unsignedBigInteger('harga_satuan');
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('jumlah_disetujui')->nullable();
            $table->timestamps();
        });

        Schema::create('luaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->string('kategori', 20);
            $table->string('jenis', 40);
            $table->string('target_keterangan')->nullable();
            $table->string('status_capaian', 20)->default('direncanakan');
            // Constrained once the publication and HKI modules add their tables.
            $table->unsignedBigInteger('publikasi_id')->nullable();
            $table->unsignedBigInteger('ki_id')->nullable();
            $table->string('url_bukti')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_status_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->string('status_dari', 20)->nullable();
            $table->string('status_ke', 20);
            $table->foreignId('oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_status_log');
        Schema::dropIfExists('luaran');
        Schema::dropIfExists('rab_item');
        Schema::dropIfExists('proposal_mitra');
        Schema::dropIfExists('proposal_anggota');
        Schema::dropIfExists('proposal');
    }
};
