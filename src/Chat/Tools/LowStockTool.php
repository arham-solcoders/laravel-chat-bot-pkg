<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\Support\Money;
use DbChatbot\Chat\Support\StockQuery;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Product;

final class LowStockTool implements Tool
{
    public function name(): string
    {
        return 'low_stock';
    }

    public function description(): string
    {
        return 'Staff and admin can list low-stock or out-of-stock products.';
    }

    public function patterns(): array
    {
        return ['low stock', 'out of stock products'];
    }

    public function matches(string $message): ?array
    {
        $message = TextNormalizer::normalize($message);

        if (TextNormalizer::isExport($message)) {
            return null;
        }

        if (preg_match('/\b(low stock|out of stock|no stock|reorder)\b/', $message) !== 1) {
            return null;
        }

        $out = preg_match('/\bout of stock\b|\bno stock\b/', $message) === 1
            && preg_match('/\blow stock\b/', $message) !== 1;

        return ['out_of_stock' => $out];
    }

    public function handle(ChatUser $user, array $args): ToolResult
    {
        if (! $user->can('viewAny', Product::class)) {
            return ToolResult::text('You do not have access to stock levels.', Suggestions::for($user), false);
        }

        $products = StockQuery::matching($args);
        $out = ($args['out_of_stock'] ?? false) === true;

        return ToolResult::table(
            $out ? 'Products with no stock on hand.' : 'Products at or below their reorder level.',
            [
                ['key' => 'sku', 'label' => 'SKU'],
                ['key' => 'name', 'label' => 'Product'],
                ['key' => 'stock', 'label' => 'Stock'],
                ['key' => 'reorder_level', 'label' => 'Reorder at'],
                ['key' => 'price', 'label' => 'Price'],
            ],
            $products->map(fn (Product $product): array => [
                'sku' => $product->sku,
                'name' => $product->name,
                'stock' => (string) $product->stock,
                'reorder_level' => (string) $product->reorder_level,
                'price' => Money::format($product->price),
            ])->all(),
            Suggestions::for($user),
        );
    }
}
