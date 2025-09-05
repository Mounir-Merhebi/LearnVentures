<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'), // default seed password
            'role' => $this->faker->randomElement(['Student','Instructor','Moderator','Admin']),
            'name' => $this->faker->name(),
            'hobbies' => $this->faker->optional()->words(3, true),
            'preferences' => $this->faker->optional()->sentence(6),
            'bio' => $this->faker->optional()->sentence(12),
            'excel_sheet_path' => null,
            'created_at' => now(),
        ];
    }

    // Optional role states
    public function student(): self { return $this->state(fn()=>['role'=>'Student']); }
    public function instructor(): self { return $this->state(fn()=>['role'=>'Instructor']); }
    public function moderator(): self { return $this->state(fn()=>['role'=>'Moderator']); }
    public function admin(): self { return $this->state(fn()=>['role'=>'Admin']); }
}
