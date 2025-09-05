<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Your test user (factory fills id/password/role/created_at)
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'Student',
        ]);

        // Some extras (optional)
        User::factory()->admin()->create([
            'name' => 'Admin Amy',
            'email' => 'admin@example.com',
        ]);

        User::factory()->student()->count(5)->create();
    }
}
