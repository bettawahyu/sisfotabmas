<?php

namespace Database\Factories;

use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penilaian>
 */
class PenilaianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'reviewer_id' => User::factory()->afterCreating(fn (User $user) => $user->assignRole(Role::REVIEWER)),
            'tahap' => Penilaian::SELEKSI,
            'status' => 'ditugaskan',
        ];
    }
}
