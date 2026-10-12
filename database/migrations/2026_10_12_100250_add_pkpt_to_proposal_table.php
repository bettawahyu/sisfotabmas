<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The system serves one campus; a joint grant with another campus (PKPT)
     * only records the partner campus on the proposal.
     */
    public function up(): void
    {
        Schema::table('proposal', function (Blueprint $table) {
            $table->boolean('is_pkpt')->default(false);
            $table->string('pt_mitra')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal', function (Blueprint $table) {
            $table->dropColumn(['is_pkpt', 'pt_mitra']);
        });
    }
};
