<?php

declare(strict_types=1);

namespace DbChatbot\Chat;

final class ToolResult
{
    /**
     * @param  list<array<string, string>>  $rows
     * @param  list<array{key: string, label: string}>  $columns
     * @param  list<string>  $quickReplies
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        public string $type,
        public string $message,
        public array $rows = [],
        public ?string $fileUrl = null,
        public array $quickReplies = [],
        public array $columns = [],
        public bool $success = true,
        public ?array $context = null,
    ) {}

    /**
     * @param  list<string>  $quickReplies
     */
    public static function text(string $message, array $quickReplies = [], bool $success = true): self
    {
        return new self(
            type: 'text',
            message: $message,
            quickReplies: $quickReplies,
            success: $success,
        );
    }

    /**
     * @param  list<array{key: string, label: string}>  $columns
     * @param  list<array<string, string>>  $rows
     * @param  list<string>  $quickReplies
     */
    public static function table(string $message, array $columns, array $rows, array $quickReplies = [], bool $success = true): self
    {
        return new self(
            type: 'table',
            message: $message,
            rows: $rows,
            columns: $columns,
            quickReplies: $quickReplies,
            success: $success,
        );
    }

    /**
     * @param  list<string>  $quickReplies
     */
    public static function file(string $message, string $fileUrl, array $quickReplies = []): self
    {
        return new self(
            type: 'file',
            message: $message,
            fileUrl: $fileUrl,
            quickReplies: $quickReplies,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'rows' => $this->rows,
            'columns' => $this->columns,
            'fileUrl' => $this->fileUrl,
            'quickReplies' => $this->quickReplies,
            'success' => $this->success,
        ];
    }
}
