<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Identity columns the proposal flow checks. The rest of the lecturer profile
     * (birth data, education history, external metrics) is added with the profile module.
     */
    public function up(): void
    {
        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('prodi_id')->nullable()->constrained('program_studi')->nullOnDelete();
            $table->string('nidn', 20)->nullable()->unique();
            $table->string('nuptk', 20)->nullable()->unique();
            $table->string('nip', 30)->nullable();
            $table->string('nama');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('jabatan_fungsional')->nullable();
            $table->string('sinta_id', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen');
    }
};
