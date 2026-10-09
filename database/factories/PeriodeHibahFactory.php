<?php

namespace Database\Factories;

use App\Enums\StatusPeriode;
use App\Models\PeriodeHibah;
use App\Models\SkemaHibah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodeHibah>
 */
class PeriodeHibahFactory extends Factory
{
    /**
     * An open call that accepts proposals today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'skema_id' => SkemaHibah::factory(),
            'tahun_anggaran' => now()->year,
            'nama' => 'Hibah Internal '.now()->year,
            'tgl_buka' => today()->subWeek(),
            'tgl_tutup' => today()->addMonth(),
            'dana_maksimal' => 15_000_000,
            'honor_diizinkan' => false,
            'status' => StatusPeriode::Dibuka,
        ];
    }

    public function ditutup(): static
    {
        return $this->state(['tgl_buka' => today()->subMonths(2), 'tgl_tutup' => today()->subMonth(), 'status' => StatusPeriode::Ditutup]);
    }
}
