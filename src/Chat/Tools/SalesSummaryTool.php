<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\Support\SalesQuery;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;

final class SalesSummaryTool implements Tool
{
    public function name(): string
    {
        return 'sales_summary';
    }

    public function description(): string
    {
        return 'Staff and admin can total paid, shipped, and delivered sales for a period.';
    }

    public function patterns(): array
    {
        return ['sales today', 'sales this week', 'sales this month'];
    }

    public function matches(string $message): ?array
    {
        $message = TextNormalizer::normalize($message);

        if (TextNormalizer::isExport($message)) {
            return null;
        }

        if (preg_match('/\b(sales|revenue)\b/', $message) !== 1) {
            return null;
        }

        return [];
    }

    public function handle(ChatUser $user, array $args): ToolResult
    {
        if (! $user->can('viewSales')) {
            return ToolResult::text('You do not have access to sales summaries.', Suggestions::for($user), false);
        }

        $summary = SalesQuery::summary($args);

        return ToolResult::table(
            'Sales '.$summary['title'].': '.$summary['count'].' orders, total '.$summary['total'].'.',
            [
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'orders', 'label' => 'Orders'],
                ['key' => 'total', 'label' => 'Total'],
            ],
            $summary['rows'],
            Suggestions::for($user),
        );
    }
}
