<?php

namespace Database\Factories;

use App\Models\Paten;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paten>
 */
class PatenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis' => 'paten_sederhana',
            'judul' => fake()->sentence(8),
            'no_permohonan' => fake()->numerify('S00202######'),
            'tgl_penerimaan' => fake()->date(),
            'status' => 'diajukan',
        ];
    }
}
