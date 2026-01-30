<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('RES-###-???')),
            'category' => fake()->randomElement(['Raw Materials', 'Concrete & Cement', 'Electrical', 'Plumbing', 'Steel & Metals']),
            'base_unit' => fake()->randomElement(['kg', 'piece', 'meter', 'liter', 'box']),
            'description' => fake()->sentence(),
        ];
    }
}
