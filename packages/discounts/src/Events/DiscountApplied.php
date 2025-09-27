<?php

namespace Rahul\Discounts\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Auth\User;
use Rahul\Discounts\Models\Discount;

class DiscountApplied
{
    use SerializesModels;

    public $user;
    public $discount;
    public $before;
    public $after;

    public function __construct(User $user, Discount $discount, float $before, float $after)
    {
        $this->user = $user;
        $this->discount = $discount;
        $this->before = $before;
        $this->after = $after;
    }
}
