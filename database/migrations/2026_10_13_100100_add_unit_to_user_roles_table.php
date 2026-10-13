<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A faculty or programme head endorses only the proposals of their own
     * unit: a prodi for a kaprodi, a fakultas for a dean. Both empty means the
     * head is not tied to a unit and sees every proposal.
     */
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->foreignId('fakultas_id')->nullable()->after('role_id')->constrained('fakultas')->nullOnDelete();
            $table->foreignId('prodi_id')->nullable()->after('fakultas_id')->constrained('program_studi')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prodi_id');
            $table->dropConstrainedForeignId('fakultas_id');
        });
    }
};
