<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The rest of the lecturer profile, the account flags and the unit scope
     * for pimpinan unit that the first migrations left out.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });

        // A pimpinan unit role is scoped to a fakultas or a program studi.
        Schema::table('user_roles', function (Blueprint $table) {
            $table->nullableMorphs('unit');
        });

        Schema::table('dosen', function (Blueprint $table) {
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('pendidikan_terakhir', 10)->nullable();
            $table->string('bidang_keahlian')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->foreignId('foto_berkas_id')->nullable()->constrained('berkas')->nullOnDelete();
            // Copied from SINTA, Scopus and Google Scholar when synced.
            $table->string('scopus_author_id', 30)->nullable();
            $table->string('google_scholar_id', 30)->nullable();
            $table->string('orcid', 19)->nullable();
            $table->decimal('skor_sinta', 10, 2)->nullable();
            $table->unsignedSmallInteger('h_index_scopus')->nullable();
            $table->unsignedSmallInteger('h_index_scholar')->nullable();
            $table->unsignedInteger('sitasi_scopus')->nullable();
            $table->unsignedInteger('sitasi_scholar')->nullable();
            $table->timestamp('sinkron_terakhir_at')->nullable();
        });

        Schema::create('riwayat_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->string('jenjang', 10);
            $table->string('institusi');
            $table->string('program_studi')->nullable();
            $table->string('negara')->nullable();
            $table->unsignedSmallInteger('tahun_lulus')->nullable();
            $table->string('judul_tugas_akhir', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_pendidikan');

        Schema::table('dosen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('foto_berkas_id');
            $table->dropColumn([
                'jenis_kelamin', 'tempat_lahir', 'tgl_lahir', 'pendidikan_terakhir', 'bidang_keahlian', 'no_hp',
                'scopus_author_id', 'google_scholar_id', 'orcid', 'skor_sinta', 'h_index_scopus', 'h_index_scholar',
                'sitasi_scopus', 'sitasi_scholar',
                'sinkron_terakhir_at',
            ]);
        });

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropMorphs('unit');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login_at']);
        });
    }
};
