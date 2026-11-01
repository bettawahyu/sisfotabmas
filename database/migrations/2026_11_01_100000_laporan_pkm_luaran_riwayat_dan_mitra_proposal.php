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
     * A community service final report carries its impact note and links; a
     * record outside the grants links to its publications, HKI and patents;
     * and a proposal's partners point at the partner register.
     */
    public function up(): void
    {
        Schema::table('laporan', function (Blueprint $table) {
            $table->text('catatan_dampak')->nullable()->after('persentase_anggaran_terpakai');
            $table->string('tautan_video')->nullable()->after('catatan_dampak');
            $table->text('tautan_media')->nullable()->after('tautan_video');
        });

        Schema::create('riwayat_kegiatan_luaran', function (Blueprint $table) {
            $table->foreignId('riwayat_kegiatan_id')->constrained('riwayat_kegiatan')->cascadeOnDelete();
            // publikasi, hki or paten, as in AlurKarya::JENIS.
            $table->string('jenis_karya', 20);
            $table->unsignedBigInteger('karya_id');
            $table->primary(['riwayat_kegiatan_id', 'jenis_karya', 'karya_id']);
            $table->index(['jenis_karya', 'karya_id']);
        });

        Schema::table('proposal_mitra', function (Blueprint $table) {
            $table->foreignId('mitra_id')->nullable()->after('proposal_id')->constrained('mitra')->nullOnDelete();
        });

        $this->sambungkanMitraLama();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_mitra', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mitra_id');
        });
        Schema::dropIfExists('riwayat_kegiatan_luaran');
        Schema::table('laporan', function (Blueprint $table) {
            $table->dropColumn(['catatan_dampak', 'tautan_video', 'tautan_media']);
        });
    }

    /**
     * Partners typed on proposals before the register existed become proposed
     * partners for LPPM to review, one per name.
     */
    private function sambungkanMitraLama(): void
    {
        $baris = DB::table('proposal_mitra')
            ->join('proposal', 'proposal.id', '=', 'proposal_mitra.proposal_id')
            ->leftJoin('dosen', 'dosen.id', '=', 'proposal.ketua_id')
            ->whereNull('proposal_mitra.mitra_id')
            ->orderBy('proposal_mitra.id')
            ->get(['proposal_mitra.id', 'proposal_mitra.nama_mitra', 'proposal_mitra.jenis_mitra', 'proposal_mitra.alamat', 'proposal_mitra.kontak', 'dosen.user_id']);

        $menurutNama = [];
        foreach ($baris as $b) {
            $kunci = mb_strtolower(trim($b->nama_mitra));
            $menurutNama[$kunci] ??= DB::table('mitra')->whereRaw('lower(trim(nama)) = ?', [$kunci])->value('id')
                ?? DB::table('mitra')->insertGetId([
                    'nama' => trim($b->nama_mitra),
                    'bidang' => $b->jenis_mitra,
                    'alamat' => $b->alamat,
                    'kontak' => $b->kontak,
                    'status' => 'usulan',
                    'is_active' => true,
                    'diusulkan_oleh' => $b->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            DB::table('proposal_mitra')->where('id', $b->id)->update(['mitra_id' => $menurutNama[$kunci]]);
        }
    }
};
