<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });

        // The role list is reference data the code depends on, so it ships with the schema.
        $now = now();
        DB::table('roles')->insert(array_map(
            fn (string $kode, string $nama) => ['kode' => $kode, 'nama' => $nama, 'created_at' => $now, 'updated_at' => $now],
            array_keys(Role::ALL),
            Role::ALL,
        ));

        // unit_id (pimpinan unit scoped to a fakultas/prodi) is added once the units table exists.
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
