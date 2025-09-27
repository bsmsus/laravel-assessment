<?php

namespace Rahul\Discounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDiscount extends Model
{
    protected $fillable = [
        'user_id', 'discount_id', 'usage_count', 'revoked_at',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }
}
