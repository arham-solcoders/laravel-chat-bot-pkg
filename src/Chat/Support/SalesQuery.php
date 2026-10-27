<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Support;

use DbChatbot\Enums\OrderStatus;
use DbChatbot\Models\Order;

final class SalesQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return array{
     *     label: string,
     *     title: string,
     *     from: string,
     *     to: string,
     *     count: int,
     *     total: string,
     *     rows: list<array{status: string, orders: string, total: string}>
     * }
     */
    public static function summary(array $args): array
    {
        $label = isset($args['date_label']) ? (string) $args['date_label'] : 'this_month';
        [$from, $to] = DateRange::bounds($label);

        if ($from === null || $to === null) {
            $label = 'this_month';
            [$from, $to] = DateRange::bounds($label);
        }

        $orders = Order::query()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered])
            ->whereBetween('placed_at', [$from, $to])
            ->get(['status', 'total']);

        $rows = [];

        foreach ([OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            $group = $orders->filter(fn (Order $order): bool => $order->status === $status);
            $rows[] = [
                'status' => $status->value,
                'orders' => (string) $group->count(),
                'total' => Money::format($group->sum(fn (Order $order): float => (float) $order->total)),
            ];
        }

        return [
            'label' => $label,
            'title' => DateRange::title($label),
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'count' => $orders->count(),
            'total' => Money::format($orders->sum(fn (Order $order): float => (float) $order->total)),
            'rows' => $rows,
        ];
    }
}
