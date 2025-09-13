<?php

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'name' => 'Grade ' . $this->faker->numberBetween(1, 12),
            'description' => $this->faker->sentence(),
            'level' => $this->faker->numberBetween(1, 12),
        ];
    }
}

