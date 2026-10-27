<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Support;

use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Order;

final class OrderFinder
{
    public static function authorized(ChatUser $user, int $number): ?Order
    {
        $order = Order::query()
            ->with(['customer', 'items', 'payment', 'shipment'])
            ->where('number', $number)
            ->first();

        if ($order === null || ! $user->can('view', $order)) {
            return null;
        }

        return $order;
    }
}
