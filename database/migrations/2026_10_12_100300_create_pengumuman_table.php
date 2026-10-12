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
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->longText('isi');
            $table->string('kategori', 20);
            $table->foreignId('periode_id')->nullable()->constrained('periode_hibah')->nullOnDelete();
            $table->string('target_peran', 20)->default('semua');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('terbit_at')->nullable();
            $table->timestamp('berakhir_at')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['terbit_at', 'berakhir_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
