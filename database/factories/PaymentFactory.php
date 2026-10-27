<?php

declare(strict_types=1);

namespace DbChatbot\Database\Factories;

use DbChatbot\Enums\PaymentStatus;
use DbChatbot\Models\Order;
use DbChatbot\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => 20,
            'method' => 'bank',
            'status' => PaymentStatus::Completed,
            'reference' => 'PAY-'.fake()->numerify('####'),
            'paid_at' => now(),
        ];
    }
}
