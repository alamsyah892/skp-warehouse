<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('VND-####'),
            'name' => fake()->company(),
            'description' => fake()->sentence(),

            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'post_code' => fake()->postcode(),
            'contact_person' => fake()->name(),
            'contact_person_position' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'fax' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'website' => fake()->url(),
            'tax_number' => fake()->numerify('##.###.###.#-###.###'),

            'is_active' => true,
        ];
    }
}
