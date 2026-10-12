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
     * Additions from the schema review: the science classification tree,
     * document templates, the endorsement history, output validation, system
     * settings and the operator and system admin roles.
     */
    public function up(): void
    {
        // Rumpun ilmu, pohon ilmu and cabang ilmu, each row pointing at its parent.
        Schema::create('rumpun_ilmu', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->string('tingkat', 10);
            $table->foreignId('induk_id')->nullable()->constrained('rumpun_ilmu')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tingkat', 'induk_id']);
        });

        Schema::table('proposal', function (Blueprint $table) {
            $table->dropColumn('rumpun_ilmu');
        });

        Schema::table('proposal', function (Blueprint $table) {
            $table->foreignId('cabang_ilmu_id')->nullable()->after('bidang_fokus_id')->constrained('rumpun_ilmu')->nullOnDelete();
        });

        // The file itself lives in berkas; a null skema or tahun applies to all.
        Schema::create('template_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 30);
            $table->foreignId('skema_id')->nullable()->constrained('skema_hibah')->nullOnDelete();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('nama');
            $table->string('versi', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['jenis', 'is_active']);
        });

        // Every endorsement decision, kept even when the proposal is resent.
        Schema::create('pengesahan_proposal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposal')->cascadeOnDelete();
            $table->foreignId('oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('keputusan', 20);
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['oleh', 'created_at']);
        });

        Schema::table('luaran', function (Blueprint $table) {
            $table->string('status_validasi', 20)->default('menunggu');
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('divalidasi_at')->nullable();
            $table->text('catatan_validasi')->nullable();
        });

        // Content the system admin can change without a deploy.
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id();
            $table->string('kunci')->unique();
            $table->text('nilai')->nullable();
            $table->string('tipe', 20)->default('teks');
            $table->string('keterangan')->nullable();
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // A fresh install already gets these from the roles migration.
        $now = now();
        foreach (['operator' => 'Operator', 'admin_sistem' => 'Admin Sistem'] as $kode => $nama) {
            if (! DB::table('roles')->where('kode', $kode)->exists()) {
                DB::table('roles')->insert(['kode' => $kode, 'nama' => $nama, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * The two roles stay: the roles migration also creates them from Role::ALL.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan');

        Schema::table('luaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('divalidasi_oleh');
            $table->dropColumn(['status_validasi', 'divalidasi_at', 'catatan_validasi']);
        });

        Schema::dropIfExists('pengesahan_proposal');
        Schema::dropIfExists('template_dokumen');

        Schema::table('proposal', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cabang_ilmu_id');
        });

        Schema::table('proposal', function (Blueprint $table) {
            $table->string('rumpun_ilmu')->nullable();
        });

        Schema::dropIfExists('rumpun_ilmu');
    }
};
