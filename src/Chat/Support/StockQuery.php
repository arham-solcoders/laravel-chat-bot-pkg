<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Support;

use DbChatbot\Models\Product;
use Illuminate\Database\Eloquent\Collection;

final class StockQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return Collection<int, Product>
     */
    public static function matching(array $args): Collection
    {
        $query = Product::query()->orderBy('stock')->orderBy('name');

        if (($args['out_of_stock'] ?? false) === true) {
            $query->where('stock', '<=', 0);
        } else {
            $query->whereColumn('stock', '<=', 'reorder_level');
        }

        return $query->get();
    }
}
