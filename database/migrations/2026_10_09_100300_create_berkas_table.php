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
        // One polymorphic table for every upload in the system.
        Schema::create('berkas', function (Blueprint $table) {
            $table->id();
            $table->morphs('pemilik');
            $table->string('kategori', 40);
            $table->string('nama_asli');
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedBigInteger('ukuran_byte');
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pemilik_type', 'pemilik_id', 'kategori']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas');
    }
};
