<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
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
            'code' => fake()->unique()->numerify('PRJ-####'),
            'po_code' => fake()->unique()->numerify('PO-####'),

            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),

            'allow_po' => true,
            'is_active' => true,
        ];
    }
}
