<?php

namespace Rahul\Discounts\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Auth\User;
use Rahul\Discounts\Models\Discount;

class DiscountAssigned
{
    use SerializesModels;

    public $user;
    public $discount;

    public function __construct(User $user, Discount $discount)
    {
        $this->user = $user;
        $this->discount = $discount;
    }
}
