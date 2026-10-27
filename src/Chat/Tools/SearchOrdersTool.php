<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\Support\Money;
use DbChatbot\Chat\Support\OrderQuery;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Order;

final class SearchOrdersTool implements Tool
{
    public function name(): string
    {
        return 'search_orders';
    }

    public function description(): string
    {
        return 'Staff and admin can filter orders by status, date, or customer email.';
    }

    public function patterns(): array
    {
        return [
            'show shipped orders this week',
            'pending orders',
            'list refunded orders',
        ];
    }

    public function matches(string $message): ?array
    {
        $message = TextNormalizer::normalize($message);

        if (TextNormalizer::isExport($message) || preg_match('/\b(my|mine)\b/', $message) === 1) {
            return null;
        }

        if (preg_match('/\borders?\b/', $message) !== 1) {
            return null;
        }

        $hasStatus = preg_match('/\b(pending|paid|shipped|delivered|cancelled|refunded)\b/', $message) === 1;
        $hasVerb = preg_match('/\b(show|list|find|search|filter)\b/', $message) === 1;

        if (! $hasStatus && ! $hasVerb) {
            return null;
        }

        return [];
    }

    public function handle(ChatUser $user, array $args): ToolResult
    {
        if (! $user->can('viewAny', Order::class)) {
            return ToolResult::text(
                'You can only view your own orders. Try "my orders".',
                Suggestions::for($user),
                false,
            );
        }

        $query = OrderQuery::filtered($args);
        $total = (clone $query)->count();
        $orders = $query->limit(25)->get();

        return ToolResult::table(
            'Showing '.$orders->count().' of '.$total.' orders.',
            [
                ['key' => 'number', 'label' => 'Order'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'placed', 'label' => 'Placed'],
            ],
            $orders->map(fn (Order $order): array => [
                'number' => (string) $order->number,
                'customer' => $order->customer?->email ?? '',
                'status' => $order->status->value,
                'total' => Money::format($order->total),
                'placed' => $order->placed_at?->toDateTimeString() ?? '',
            ])->all(),
            Suggestions::for($user),
        );
    }
}
