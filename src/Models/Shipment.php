<?php

declare(strict_types=1);

namespace DbChatbot\Models;

use DbChatbot\Database\Factories\ShipmentFactory;
use DbChatbot\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'carrier', 'tracking_number', 'status', 'shipped_at', 'delivered_at'])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    protected static function newFactory(): ShipmentFactory
    {
        return ShipmentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
