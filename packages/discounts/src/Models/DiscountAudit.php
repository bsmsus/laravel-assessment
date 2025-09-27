<?php

namespace Rahul\Discounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountAudit extends Model
{
    protected $fillable = [
        'user_id', 'discount_id', 'action', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
