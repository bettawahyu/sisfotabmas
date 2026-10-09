<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dosen>
 */
class DosenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->afterCreating(fn (User $user) => $user->assignRole(Role::DOSEN)),
            'nama' => fake()->name(),
            'nidn' => fake()->unique()->numerify('0#########'),
            'sinta_id' => fake()->numerify('######'),
        ];
    }

    /**
     * A lecturer whose national identifiers are not filled in yet.
     */
    public function tanpaIdentitas(): static
    {
        return $this->state(['nidn' => null, 'nuptk' => null, 'sinta_id' => null]);
    }
}
