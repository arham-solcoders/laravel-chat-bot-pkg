<?php

declare(strict_types=1);

namespace DbChatbot\Database\Factories;

use DbChatbot\Models\Order;
use DbChatbot\Models\OrderItem;
use DbChatbot\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 5, 80);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'quantity' => $quantity,
            'unit_price' => $price,
            'line_total' => round($price * $quantity, 2),
        ];
    }
}
