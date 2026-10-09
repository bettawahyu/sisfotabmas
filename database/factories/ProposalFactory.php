<?php

namespace Database\Factories;

use App\Enums\JenisLuaran;
use App\Enums\KategoriRab;
use App\Models\Berkas;
use App\Models\Dosen;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periode_id' => PeriodeHibah::factory(),
            'ketua_id' => Dosen::factory(),
            'judul' => fake()->sentence(8),
            'ringkasan' => fake()->paragraph(),
        ];
    }

    /**
     * A draft with everything the send checks ask for: substance PDF, a budget
     * line under the ceiling and one mandatory output.
     */
    public function lengkap(): static
    {
        return $this->afterCreating(function (Proposal $proposal) {
            $proposal->berkas()->create([
                'kategori' => Berkas::SUBSTANSI,
                'nama_asli' => 'substansi.pdf',
                'path' => "proposal/{$proposal->id}/substansi.pdf",
                'mime' => 'application/pdf',
                'ukuran_byte' => 1024,
            ]);
            $proposal->rab()->create([
                'kategori' => KategoriRab::Bahan,
                'uraian' => 'ATK dan bahan habis pakai',
                'volume' => 1,
                'satuan' => 'paket',
                'harga_satuan' => 5_000_000,
                'total' => 5_000_000,
            ]);
            $proposal->luaran()->create(['kategori' => 'wajib', 'jenis' => JenisLuaran::ArtikelJurnal]);
        });
    }
}
