<?php

declare(strict_types=1);

namespace DbChatbot\Models;

use DbChatbot\Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'email', 'phone', 'company'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
