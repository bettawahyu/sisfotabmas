<?php

namespace Database\Factories;

use App\Models\Hki;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hki>
 */
class HkiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis' => 'hak_cipta',
            'jenis_ciptaan' => 'program_komputer',
            'judul' => fake()->sentence(6),
            'no_pencatatan' => fake()->numerify('EC00202######'),
            'tgl_pencatatan' => fake()->date(),
            'status' => 'tercatat',
        ];
    }
}
