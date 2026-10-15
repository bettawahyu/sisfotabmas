<?php

use App\Enums\JabatanFungsional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reference data the system admin keeps: units can be switched off instead of
     * deleted, journal ranks move out of the code, and the free-text lecturer rank
     * becomes one of the national ranks.
     */
    public function up(): void
    {
        Schema::table('fakultas', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('program_studi', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::create('peringkat_jurnal', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $peringkat = [
            'scopus_q1' => 'Scopus Q1', 'scopus_q2' => 'Scopus Q2', 'scopus_q3' => 'Scopus Q3', 'scopus_q4' => 'Scopus Q4',
            'sinta_1' => 'SINTA 1', 'sinta_2' => 'SINTA 2', 'sinta_3' => 'SINTA 3', 'sinta_4' => 'SINTA 4', 'sinta_5' => 'SINTA 5', 'sinta_6' => 'SINTA 6',
            'lainnya' => 'Lainnya',
        ];
        $urutan = 0;
        DB::table('peringkat_jurnal')->insert(collect($peringkat)->map(fn ($nama, $kode) => [
            'kode' => $kode, 'nama' => $nama, 'urutan' => ++$urutan * 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ])->values()->all());

        // Ranks that cannot be read are cleared so the lecturer picks one again.
        DB::table('dosen')->whereNotNull('jabatan_fungsional')->orderBy('id')->select(['id', 'jabatan_fungsional'])
            ->each(function ($dosen) {
                DB::table('dosen')->where('id', $dosen->id)
                    ->update(['jabatan_fungsional' => JabatanFungsional::dariTeks($dosen->jabatan_fungsional)?->value]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peringkat_jurnal');

        Schema::table('program_studi', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('fakultas', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
