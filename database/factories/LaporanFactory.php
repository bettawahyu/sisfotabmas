<?php

namespace Database\Factories;

use App\Models\Laporan;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laporan>
 */
class LaporanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'jenis' => Laporan::KEMAJUAN,
            'ringkasan' => fake()->paragraph(),
            'persentase_capaian' => 50,
            'status' => 'draft',
        ];
    }
}
