<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Local development accounts; every password is "password".
        $accounts = [
            'admin@example.com' => ['Admin LPPM', Role::ADMIN_LPPM],
            'dosen@example.com' => ['Dosen', Role::DOSEN],
            'reviewer@example.com' => ['Reviewer', Role::REVIEWER],
        ];

        foreach ($accounts as $email => [$name, $role]) {
            User::factory()->create(['name' => $name, 'email' => $email])->assignRole($role);
        }
    }
}
