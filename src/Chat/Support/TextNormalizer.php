<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Support;

final class TextNormalizer
{
    /**
     * @var array<string, string>
     */
    private const TYPOS = [
        'oder' => 'order',
        'ordr' => 'order',
        'ordres' => 'orders',
        'wher' => 'where',
        'wheer' => 'where',
        'staus' => 'status',
        'statu' => 'status',
        'shiped' => 'shipped',
        'pendng' => 'pending',
        'pendin' => 'pending',
        'delivred' => 'delivered',
        'canceled' => 'cancelled',
        'cancled' => 'cancelled',
        'refundd' => 'refunded',
        'stok' => 'stock',
        'custmer' => 'customer',
        'customr' => 'customer',
        'sals' => 'sales',
        'wek' => 'week',
        'thisweek' => 'this week',
        'thismonth' => 'this month',
        'lowstock' => 'low stock',
    ];

    public static function normalize(string $message): string
    {
        $message = mb_strtolower(trim($message));
        $message = preg_replace('/[^\p{L}\p{N}\s@.\-_+]/u', ' ', $message) ?? $message;
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        $words = array_map(
            fn (string $word): string => self::TYPOS[$word] ?? $word,
            $message === '' ? [] : explode(' ', $message),
        );

        return trim(preg_replace('/\s+/', ' ', implode(' ', $words)) ?? '');
    }

    public static function isExport(string $normalized): bool
    {
        return preg_match('/\b(pdf|docx|word|doc|export|download|report)\b/', $normalized) === 1;
    }
}
