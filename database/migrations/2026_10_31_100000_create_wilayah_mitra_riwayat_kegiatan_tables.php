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
     * Provinces and regencies (Kepmendagri 300.2.2-2138/2025), the partner
     * register kept by LPPM, and lecturers' research and community service
     * records outside the LPPM grant cycle.
     */
    public function up(): void
    {
        Schema::create('provinsi', function (Blueprint $table) {
            $table->string('kode', 2)->primary();
            $table->string('nama', 100);
        });

        Schema::create('kabupaten_kota', function (Blueprint $table) {
            $table->string('kode', 5)->primary();
            $table->string('provinsi_kode', 2);
            $table->string('nama', 100);
            $table->foreign('provinsi_kode')->references('kode')->on('provinsi');
            $table->index('provinsi_kode');
        });

        $this->isiWilayah();

        // A partner a lecturer adds is used on their record at once; LPPM
        // decides whether it joins the register everyone picks from.
        Schema::create('mitra', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('bidang')->nullable();
            $table->string('alamat')->nullable();
            $table->string('kontak')->nullable();
            $table->string('kabupaten_kota_kode', 5)->nullable();
            $table->string('status', 20)->default('usulan');
            $table->boolean('is_active')->default(true);
            $table->foreignId('diusulkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamps();
            $table->foreign('kabupaten_kota_kode')->references('kode')->on('kabupaten_kota')->nullOnDelete();
            $table->index(['status', 'is_active']);
        });

        Schema::create('riwayat_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_kegiatan', 20);
            $table->string('judul', 500);
            $table->unsignedSmallInteger('tahun_mulai');
            $table->unsignedSmallInteger('tahun_selesai')->nullable();
            $table->string('status_kegiatan', 20);
            $table->string('sumber_dana', 30);
            $table->string('pemberi_dana')->nullable();
            $table->string('skema')->nullable();
            $table->unsignedBigInteger('nilai_dana')->nullable();
            $table->string('no_kontrak')->nullable();
            $table->foreignId('bidang_fokus_id')->nullable()->constrained('bidang_fokus')->nullOnDelete();
            $table->string('kabupaten_kota_kode', 5)->nullable();
            $table->string('alamat_lokasi')->nullable();
            $table->text('ringkasan')->nullable();
            // Community service only.
            $table->unsignedInteger('jumlah_sasaran')->nullable();
            $table->text('catatan_dampak')->nullable();
            $table->string('tautan_video')->nullable();
            $table->text('tautan_media')->nullable();

            $table->foreignId('diinput_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_verifikasi', 20)->default('menunggu');
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('diverifikasi_lppm_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_lppm_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kabupaten_kota_kode')->references('kode')->on('kabupaten_kota')->nullOnDelete();
            $table->index(['jenis_kegiatan', 'status_verifikasi']);
            $table->index(['jenis_kegiatan', 'tahun_mulai']);
        });

        Schema::create('riwayat_kegiatan_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riwayat_kegiatan_id')->constrained('riwayat_kegiatan')->cascadeOnDelete();
            $table->string('tipe', 20);
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('nama');
            // NIM for a student, institution for an outsider.
            $table->string('keterangan')->nullable();
            $table->string('peran', 20);
            $table->unsignedTinyInteger('urutan');
            $table->timestamps();
            $table->unique(['riwayat_kegiatan_id', 'urutan']);
        });

        Schema::create('riwayat_kegiatan_mitra', function (Blueprint $table) {
            $table->foreignId('riwayat_kegiatan_id')->constrained('riwayat_kegiatan')->cascadeOnDelete();
            $table->foreignId('mitra_id')->constrained('mitra')->restrictOnDelete();
            $table->primary(['riwayat_kegiatan_id', 'mitra_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_kegiatan_mitra');
        Schema::dropIfExists('riwayat_kegiatan_anggota');
        Schema::dropIfExists('riwayat_kegiatan');
        Schema::dropIfExists('mitra');
        Schema::dropIfExists('kabupaten_kota');
        Schema::dropIfExists('provinsi');
    }

    /**
     * Province codes have two digits and regency codes four ("11.01" is kept as "1101").
     */
    private function isiWilayah(): void
    {
        $baris = array_map('str_getcsv', array_slice(file(database_path('data/wilayah.csv'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), 1));

        $provinsi = [];
        $kabupaten = [];
        foreach ($baris as [$kode, $nama]) {
            if (strlen($kode) === 2) {
                $provinsi[] = ['kode' => $kode, 'nama' => $nama];
            } else {
                $kabupaten[] = ['kode' => str_replace('.', '', $kode), 'provinsi_kode' => substr($kode, 0, 2), 'nama' => $nama];
            }
        }

        DB::table('provinsi')->insert($provinsi);
        foreach (array_chunk($kabupaten, 200) as $potongan) {
            DB::table('kabupaten_kota')->insert($potongan);
        }
    }
};
