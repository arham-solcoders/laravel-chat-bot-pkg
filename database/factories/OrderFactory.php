<?php

declare(strict_types=1);

namespace DbChatbot\Database\Factories;

use DbChatbot\Enums\OrderStatus;
use DbChatbot\Models\Customer;
use DbChatbot\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 200);
        $tax = round($subtotal * 0.08, 2);
        $shipping = 8.00;

        return [
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Pending,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($subtotal + $tax + $shipping, 2),
            'placed_at' => now(),
            'notes' => null,
        ];
    }
}
