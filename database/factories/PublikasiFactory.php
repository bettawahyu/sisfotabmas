<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Publikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publikasi>
 */
class PublikasiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis' => 'jurnal_nasional',
            'judul' => fake()->sentence(10),
            'nama_wadah' => 'Jurnal '.fake()->words(3, true),
            'tahun' => (int) now()->year,
            'peringkat' => 'sinta_'.fake()->numberBetween(1, 6),
            'status' => 'published',
        ];
    }

    /**
     * Lists the given lecturer as first author.
     */
    public function olehDosen(Dosen $dosen): static
    {
        return $this->afterCreating(fn (Publikasi $publikasi) => $publikasi->penulis()->create([
            'dosen_id' => $dosen->id, 'nama_penulis' => $dosen->nama, 'urutan' => 1, 'is_korespondensi' => true,
        ]));
    }
}
