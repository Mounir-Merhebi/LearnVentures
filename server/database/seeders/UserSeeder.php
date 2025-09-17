<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('users')->insert([
            [
                'id' => (string) Str::uuid(),
                'email' => 'student@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Student',
                'name' => 'Student One',
                'hobbies' => 'Reading, Chess',
                'preferences' => 'Dark mode; email alerts',
                'bio' => 'Curious learner.',
                'created_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'email' => 'instructor@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Instructor',
                'name' => 'Instructor Jane',
                'hobbies' => 'Hiking',
                'preferences' => 'Light mode',
                'bio' => 'Teaches math & physics.',
                'created_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'email' => 'moderator@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Moderator',
                'name' => 'Mod Mike',
                'hobbies' => null,
                'preferences' => null,
                'bio' => 'Keeps things tidy.',
                'created_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Admin',
                'name' => 'Admin Amy',
                'hobbies' => 'Running',
                'preferences' => 'Email alerts only',
                'bio' => 'System administrator.',
                'created_at' => $now,
            ],
        ]);
    }
}
