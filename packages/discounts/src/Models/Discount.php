<?php

namespace Rahul\Discounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $fillable = [
        'name', 'type', 'value', 'active', 'expires_at',
        'usage_limit_per_user', 'usage_limit_total', 'usage_count',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function userDiscounts(): HasMany
    {
        return $this->hasMany(UserDiscount::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(DiscountAudit::class);
    }

    public function isActive(): bool
    {
        return $this->active && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
