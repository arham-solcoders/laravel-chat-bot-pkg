<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Support;

use DbChatbot\Enums\OrderStatus;
use DbChatbot\Models\Order;
use Illuminate\Database\Eloquent\Builder;

final class OrderQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return Builder<Order>
     */
    public static function filtered(array $args): Builder
    {
        $status = isset($args['status']) ? OrderStatus::tryFrom((string) $args['status']) : null;
        [$from, $to] = DateRange::bounds(isset($args['date_label']) ? (string) $args['date_label'] : null);
        $email = self::email($args);

        return Order::query()
            ->with(['customer'])
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($from !== null && $to !== null, fn (Builder $query) => $query->whereBetween('placed_at', [$from, $to]))
            ->when($email !== null, fn (Builder $query) => $query->whereHas(
                'customer',
                fn (Builder $customers) => $customers->where('email', $email),
            ))
            ->orderByDesc('placed_at')
            ->orderByDesc('number');
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public static function email(array $args): ?string
    {
        if (! isset($args['email']) || ! is_string($args['email'])) {
            return null;
        }

        $email = strtolower(trim($args['email']));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
