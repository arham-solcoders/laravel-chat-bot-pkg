<?php

declare(strict_types=1);

namespace DbChatbot\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'kind', 'format', 'path', 'meta'])]
class Report extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Report $report): void {
            if ($report->path !== '') {
                Storage::disk('reports')->delete($report->path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function downloadName(): string
    {
        $meta = $this->meta ?? [];
        $label = $this->kind;

        if (isset($meta['order_number'])) {
            $label .= '-'.$meta['order_number'];
        }

        return $label.'.'.$this->format;
    }
}
