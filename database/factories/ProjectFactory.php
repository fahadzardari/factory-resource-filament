<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->randomElement(['Building', 'Factory', 'Plaza', 'Tower']),
            'code' => strtoupper(fake()->bothify('PRJ-###-??')),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'active', 'completed']),
            'start_date' => now()->subDays(rand(30, 90)),
            'end_date' => now()->addDays(rand(30, 180)),
        ];
    }
}
