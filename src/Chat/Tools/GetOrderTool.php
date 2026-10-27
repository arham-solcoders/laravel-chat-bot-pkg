<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\Messages;
use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\Support\EntityExtractor;
use DbChatbot\Chat\Support\Money;
use DbChatbot\Chat\Support\OrderFinder;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Order;

final class GetOrderTool implements Tool
{
    public function name(): string
    {
        return 'get_order';
    }

    public function description(): string
    {
        return 'Look up one order by number, including items, payment, shipment, and status.';
    }

    public function patterns(): array
    {
        return [
            'order 2222',
            'order #2222',
            'status of 2222',
            'where is my order 2222',
        ];
    }

    public function matches(string $message): ?array
    {
        $message = TextNormalizer::normalize($message);

        if (TextNormalizer::isExport($message)) {
            return null;
        }

        $number = EntityExtractor::orderNumber($message);

        if ($number === null) {
            return null;
        }

        $mentionsOrder = preg_match('/\b(order|status|where|track|tracking|shipment)\b/', $message) === 1;
        $onlyNumber = preg_match('/^\d{3,8}$/', $message) === 1;

        if (! $mentionsOrder && ! $onlyNumber) {
            return null;
        }

        return ['order_number' => $number];
    }

    public function handle(ChatUser $user, array $args): ToolResult
    {
        $number = isset($args['order_number']) ? (int) $args['order_number'] : 0;
        $order = $number > 0 ? OrderFinder::authorized($user, $number) : null;

        if ($order === null) {
            return ToolResult::text(Messages::ORDER_NOT_FOUND, Suggestions::for($user), false);
        }

        $result = ToolResult::table(
            $this->summary($order),
            [
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'quantity', 'label' => 'Qty'],
                ['key' => 'unit_price', 'label' => 'Unit price'],
                ['key' => 'line_total', 'label' => 'Line total'],
            ],
            $order->items->map(fn ($item): array => [
                'product' => $item->product_name,
                'quantity' => (string) $item->quantity,
                'unit_price' => Money::format($item->unit_price),
                'line_total' => Money::format($item->line_total),
            ])->all(),
            array_values(array_unique(array_merge(
                ['PDF for this order', 'Word report for this order'],
                Suggestions::for($user),
            ))),
        );

        $result->context = ['last_order_number' => $order->number];

        return $result;
    }

    private function summary(Order $order): string
    {
        $customer = $order->customer;
        $lines = [
            'Order '.$order->number.' is '.$order->status->value.'.',
            'Customer: '.($customer->name ?? 'Unknown').' ('.($customer->email ?? 'unknown').')',
            'Placed: '.($order->placed_at?->toDateTimeString() ?? ''),
            'Subtotal '.Money::format($order->subtotal)
                .', tax '.Money::format($order->tax)
                .', shipping '.Money::format($order->shipping)
                .', total '.Money::format($order->total).'.',
        ];

        if ($order->payment === null) {
            $lines[] = 'Payment: none yet.';
        } else {
            $lines[] = 'Payment: '.$order->payment->method.', '.$order->payment->status->value.', '.Money::format($order->payment->amount).'.';
        }

        if ($order->shipment === null) {
            $lines[] = 'Shipment: not shipped yet.';
        } else {
            $lines[] = 'Shipment: '.$order->shipment->carrier
                .', tracking '.($order->shipment->tracking_number ?? 'n/a')
                .', '.$order->shipment->status->value.'.';
        }

        $lines[] = 'Timeline:';

        foreach ($order->statusTimeline() as $event) {
            $lines[] = '- '.$event['label'].($event['at'] !== '' ? ': '.$event['at'] : '');
        }

        return implode("\n", $lines);
    }
}
