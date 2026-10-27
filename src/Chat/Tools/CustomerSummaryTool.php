<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\Messages;
use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\Support\EntityExtractor;
use DbChatbot\Chat\Support\Money;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;
use DbChatbot\Enums\OrderStatus;
use DbChatbot\Models\Customer;

final class CustomerSummaryTool implements Tool
{
    public function name(): string
    {
        return 'customer_summary';
    }

    public function description(): string
    {
        return 'Staff and admin can look up a customer by email.';
    }

    public function patterns(): array
    {
        return ['customer john@x.com'];
    }

    public function matches(string $message): ?array
    {
        $message = TextNormalizer::normalize($message);

        if (TextNormalizer::isExport($message)) {
            return null;
        }

        $email = EntityExtractor::email($message);
        $mentionsCustomer = preg_match('/\b(customer|client)\b/', $message) === 1;

        if ($email === null || ! $mentionsCustomer) {
            return null;
        }

        return ['email' => $email];
    }

    public function handle(ChatUser $user, array $args): ToolResult
    {
        $email = isset($args['email']) ? strtolower(trim((string) $args['email'])) : '';
        $allowed = $user->can('viewAny', Customer::class);
        $customer = ($allowed && filter_var($email, FILTER_VALIDATE_EMAIL))
            ? Customer::query()->where('email', $email)->first()
            : null;

        if ($customer === null || ! $user->can('view', $customer)) {
            return ToolResult::text(Messages::CUSTOMER_NOT_FOUND, Suggestions::for($user), false);
        }

        $orders = $customer->orders()->orderByDesc('placed_at')->limit(5)->get();
        $spent = $customer->orders()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered])
            ->sum('total');

        return ToolResult::table(
            implode("\n", [
                $customer->name.' ('.$customer->email.')',
                'Phone: '.($customer->phone ?? 'n/a'),
                'Company: '.($customer->company ?? 'n/a'),
                'Orders: '.$customer->orders()->count().', spent '.Money::format($spent).'.',
            ]),
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
