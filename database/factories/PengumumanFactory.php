<?php

namespace Database\Factories;

use App\Models\Pengumuman;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pengumuman>
 */
class PengumumanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => $judul = fake()->sentence(6),
            'slug' => Str::slug($judul).'-'.fake()->unique()->numberBetween(1, 99999),
            'isi' => fake()->paragraphs(2, true),
            'kategori' => 'umum',
            'terbit_at' => now()->subDay(),
        ];
    }
}
