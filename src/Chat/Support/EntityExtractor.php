<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Support;

final class EntityExtractor
{
    /**
     * @return array<string, int|string>
     */
    public static function extract(string $normalized): array
    {
        $values = [
            'order_number' => self::orderNumber($normalized),
            'email' => self::email($normalized),
            'status' => self::status($normalized),
            'date_label' => self::dateLabel($normalized),
        ];

        return array_filter(
            $values,
            static fn (int|string|null $value): bool => $value !== null && $value !== '',
        );
    }

    public static function orderNumber(string $message): ?int
    {
        $patterns = [
            '/\b(?:order|status|track|tracking)\s+of\s+(\d{3,8})\b/',
            '/\b(?:order|status|track|tracking)\s+(\d{3,8})\b/',
            '/\b(\d{3,8})\b(?:\s+\w+){0,4}\s+\b(?:order|status)\b/',
            '/\b(?:pdf|word|docx|for|of)\s+(\d{3,8})\b/',
            '/^(\d{3,8})$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    public static function email(string $message): ?string
    {
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/', $message, $matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    public static function status(string $message): ?string
    {
        if (preg_match('/\b(pending|paid|shipped|delivered|cancelled|refunded)\b/', $message, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public static function dateLabel(string $message): ?string
    {
        $patterns = [
            'last_7_days' => '/\blast 7 days\b/',
            'yesterday' => '/\byesterday\b/',
            'today' => '/\btoday\b/',
            'this_week' => '/\bthis week\b/',
            'last_week' => '/\blast week\b/',
            'this_month' => '/\bthis month\b/',
            'last_month' => '/\blast month\b/',
        ];

        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return $label;
            }
        }

        return null;
    }
}
