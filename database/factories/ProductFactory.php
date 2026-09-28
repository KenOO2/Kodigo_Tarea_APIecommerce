<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(0, 200),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'image_url' => 'https://picsum.photos/seed/'.fake()->uuid().'/600/600',
            'is_active' => true,
        ];
    }
}
