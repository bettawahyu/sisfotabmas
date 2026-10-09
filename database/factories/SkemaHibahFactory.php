<?php

namespace Database\Factories;

use App\Enums\JenisKegiatan;
use App\Enums\SumberDana;
use App\Models\SkemaHibah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkemaHibah>
 */
class SkemaHibahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('SK-###'),
            'nama' => 'Penelitian Dosen Pemula',
            'jenis_kegiatan' => JenisKegiatan::Penelitian,
            'sumber_dana' => SumberDana::Internal,
            'dana_maksimal_default' => 15_000_000,
        ];
    }

    public function pkm(): static
    {
        return $this->state(['nama' => 'PkM Desa Binaan', 'jenis_kegiatan' => JenisKegiatan::Pkm]);
    }
}
