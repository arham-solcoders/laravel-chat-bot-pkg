<?php

declare(strict_types=1);

namespace DbChatbot\Models;

use DbChatbot\Database\Factories\OrderFactory;
use DbChatbot\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'number',
    'customer_id',
    'status',
    'subtotal',
    'tax',
    'shipping',
    'total',
    'placed_at',
    'notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if ($order->number !== null) {
                return;
            }

            $max = (int) static::query()->max('number');
            $order->number = max(1000, $max) + 1;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    /**
     * @return list<array{label: string, at: string}>
     */
    public function statusTimeline(): array
    {
        $events = [[
            'label' => 'Placed',
            'at' => $this->placed_at?->toDateTimeString() ?? '',
        ]];

        if ($this->payment?->paid_at !== null) {
            $events[] = [
                'label' => 'Paid',
                'at' => $this->payment->paid_at->toDateTimeString(),
            ];
        }

        if ($this->shipment?->shipped_at !== null) {
            $events[] = [
                'label' => 'Shipped',
                'at' => $this->shipment->shipped_at->toDateTimeString(),
            ];
        }

        if ($this->shipment?->delivered_at !== null) {
            $events[] = [
                'label' => 'Delivered',
                'at' => $this->shipment->delivered_at->toDateTimeString(),
            ];
        }

        if ($this->status === OrderStatus::Cancelled) {
            $events[] = ['label' => 'Cancelled', 'at' => ''];
        }

        if ($this->status === OrderStatus::Refunded) {
            $events[] = ['label' => 'Refunded', 'at' => ''];
        }

        return $events;
    }
}
