<?php

declare(strict_types=1);

namespace DbChatbot\Database\Factories;

use DbChatbot\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 5, 180),
            'stock' => fake()->numberBetween(0, 80),
            'reorder_level' => 10,
        ];
    }
}
