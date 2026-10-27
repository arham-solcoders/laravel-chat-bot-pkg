<?php

declare(strict_types=1);

namespace DbChatbot\Database\Factories;

use DbChatbot\Enums\ShipmentStatus;
use DbChatbot\Models\Order;
use DbChatbot\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'carrier' => 'UPS',
            'tracking_number' => 'TRK'.fake()->numerify('########'),
            'status' => ShipmentStatus::Shipped,
            'shipped_at' => now(),
            'delivered_at' => null,
        ];
    }
}
