<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\Messages;
use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\Support\EntityExtractor;
use DbChatbot\Chat\Support\OrderFinder;
use DbChatbot\Chat\Support\OrderQuery;
use DbChatbot\Chat\Support\SalesQuery;
use DbChatbot\Chat\Support\StockQuery;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Order;
use DbChatbot\Models\Product;
use DbChatbot\Models\Report;
use DbChatbot\Reports\ReportService;

final class ExportReportTool implements Tool
{
    public function __construct(private ReportService $reports) {}

    public function name(): string
    {
        return 'export_report';
    }

    public function description(): string
    {
        return 'Build a PDF or Word report. PDF is the default when no format is named.';
    }

    public function patterns(): array
    {
        return [
            'pdf for order 2222',
            'word report for order 2222',
            'export shipped orders as pdf',
            'sales report this month docx',
            'download it',
            'report',
        ];
    }

    public function matches(string $message): ?array
    {
        $message = TextNormalizer::normalize($message);

        if (! TextNormalizer::isExport($message)) {
            return null;
        }

        $format = 'pdf';

        if (preg_match_all('/\b(pdf|docx|word|doc)\b/', $message, $found) >= 1 && $found[1] !== []) {
            $last = $found[1][array_key_last($found[1])];
            $format = in_array($last, ['docx', 'word', 'doc'], true) ? 'docx' : 'pdf';
        }

        $number = EntityExtractor::orderNumber($message);
        $kind = 'order';

        if (preg_match('/\b(sales|revenue)\b/', $message) === 1) {
            $kind = 'sales';
        } elseif (preg_match('/\b(low stock|out of stock)\b/', $message) === 1) {
            $kind = 'low_stock';
        } elseif (preg_match('/\borders\b/', $message) === 1) {
            $kind = 'orders';
        }

        $args = [
            'format' => $format,
            'kind' => $kind,
            'follow_up' => $kind === 'order' && $number === null,
            'out_of_stock' => preg_match('/\bout of stock\b/', $message) === 1,
        ];

        if ($number !== null) {
            $args['order_number'] = $number;
        }

        return $args;
    }

    public function handle(ChatUser $user, array $args): ToolResult
    {
        $format = ($args['format'] ?? 'pdf') === 'docx' ? 'docx' : 'pdf';
        $kind = (string) ($args['kind'] ?? 'order');

        $report = match ($kind) {
            'sales' => $this->sales($user, $args, $format),
            'low_stock' => $this->lowStock($user, $args, $format),
            'orders' => $this->orders($user, $args, $format),
            default => $this->order($user, $args, $format),
        };

        if ($report instanceof ToolResult) {
            return $report;
        }

        $label = $format === 'docx' ? 'Word' : 'PDF';

        return ToolResult::file(
            'Your '.$label.' report is ready. The link expires in 15 minutes.',
            $this->reports->temporaryUrl($report),
            Suggestions::for($user),
        );
    }

    private function order(ChatUser $user, array $args, string $format): ToolResult|Report
    {
        if (! isset($args['order_number'])) {
            return ToolResult::text('Open an order first, then ask me to download it.', Suggestions::for($user), false);
        }

        $order = OrderFinder::authorized($user, (int) $args['order_number']);

        if ($order === null) {
            return ToolResult::text(Messages::ORDER_NOT_FOUND, Suggestions::for($user), false);
        }

        return $this->reports->order($user, $order, $format);
    }

    private function orders(ChatUser $user, array $args, string $format): ToolResult|Report
    {
        if (! $user->can('viewAny', Order::class)) {
            return ToolResult::text('You can only view your own orders. Try "my orders".', Suggestions::for($user), false);
        }

        $orders = OrderQuery::filtered($args)->limit(200)->get();

        return $this->reports->orders($user, $orders, [
            'status' => $args['status'] ?? null,
            'date_label' => $args['date_label'] ?? null,
            'email' => OrderQuery::email($args),
        ], $format);
    }

    private function sales(ChatUser $user, array $args, string $format): ToolResult|Report
    {
        if (! $user->can('viewSales')) {
            return ToolResult::text('You do not have access to sales summaries.', Suggestions::for($user), false);
        }

        return $this->reports->sales($user, SalesQuery::summary($args), $format);
    }

    private function lowStock(ChatUser $user, array $args, string $format): ToolResult|Report
    {
        if (! $user->can('viewAny', Product::class)) {
            return ToolResult::text('You do not have access to stock levels.', Suggestions::for($user), false);
        }

        return $this->reports->lowStock($user, StockQuery::matching($args), $args, $format);
    }
}
