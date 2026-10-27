<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\Support\Money;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;

final class MyOrdersTool implements Tool
{
    public function name(): string
    {
        return 'my_orders';
    }

    public function description(): string
    {
        return 'List the signed-in customer\'s 10 most recent orders.';
    }

    public function patterns(): array
    {
        return ['my orders', 'recent orders', 'orders of mine'];
    }

    public function matches(string $message): ?array
    {
        $message = TextNormalizer::normalize($message);

        if (TextNormalizer::isExport($message)) {
            return null;
        }

        $personal = preg_match('/\b(my|mine|recent|latest|history)\b/', $message) === 1;
        $orders = preg_match('/\borders?\b/', $message) === 1;

        if (! $personal || ! $orders) {
            return null;
        }

        return [];
    }

    public function handle(ChatUser $user, array $args): ToolResult
    {
        $customer = $user->customer;

        if ($customer === null) {
            return ToolResult::text('You have no orders linked to this account.', Suggestions::for($user));
        }

        $orders = $customer->orders()
            ->where('customer_id', $customer->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('number')
            ->limit(10)
            ->get();

        if ($orders->isEmpty()) {
            return ToolResult::text('You have no orders yet.', Suggestions::for($user));
        }

        return ToolResult::table(
            'Here are your '.$orders->count().' most recent orders.',
            [
                ['key' => 'number', 'label' => 'Order'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'placed', 'label' => 'Placed'],
            ],
            $orders->map(fn ($order): array => [
                'number' => (string) $order->number,
                'status' => $order->status->value,
                'total' => Money::format($order->total),
                'placed' => $order->placed_at?->toDateTimeString() ?? '',
            ])->all(),
            Suggestions::for($user),
        );
    }
}
