<?php

namespace Rahul\Discounts\Services;

use Illuminate\Support\Facades\DB;
use Rahul\Discounts\Models\{Discount, UserDiscount, DiscountAudit};
use Rahul\Discounts\Events\{DiscountAssigned, DiscountRevoked, DiscountApplied};
use Illuminate\Foundation\Auth\User;

class DiscountService
{
    public function assign(User $user, Discount $discount): void
    {
        if (!$discount->isActive()) return;

        $userDiscount = UserDiscount::firstOrCreate(
            ['user_id' => $user->id, 'discount_id' => $discount->id],
            ['usage_count' => 0]
        );

        event(new DiscountAssigned($user, $discount));

        DiscountAudit::create([
            'user_id' => $user->id,
            'discount_id' => $discount->id,
            'action' => 'assigned',
        ]);
    }

    public function revoke(User $user, Discount $discount): void
    {
        $userDiscount = UserDiscount::where('user_id', $user->id)
            ->where('discount_id', $discount->id)
            ->first();

        if ($userDiscount) {
            $userDiscount->update(['revoked_at' => now()]);
            event(new DiscountRevoked($user, $discount));
            DiscountAudit::create([
                'user_id' => $user->id,
                'discount_id' => $discount->id,
                'action' => 'revoked',
            ]);
        }
    }

    public function apply(User $user, float $amount): float
    {
        return DB::transaction(function () use ($user, $amount) {
            $discounts = Discount::whereHas('userDiscounts', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereNull('revoked_at');
            })->get();

            $eligible = $discounts->filter(fn($d) => $d->isActive());

            $stackingOrder = config('discounts.stacking_order');
            $eligible = $eligible->sortBy(function ($d) use ($stackingOrder) {
                return array_search($d->type, $stackingOrder);
            });

            $final = $amount;
            foreach ($eligible as $discount) {
                $before = $final;
                if ($discount->type === 'percent') {
                    $cap = config('discounts.max_percentage_cap');
                    $value = min($discount->value, $cap);
                    $final -= ($final * $value / 100);
                } else {
                    $final -= $discount->value;
                }

                $rounding = config('discounts.rounding');
                $final = $rounding($final);

                // usage checks
                $userDiscount = UserDiscount::where('user_id', $user->id)
                    ->where('discount_id', $discount->id)
                    ->lockForUpdate()
                    ->first();

                if ($discount->usage_limit_per_user && $userDiscount->usage_count >= $discount->usage_limit_per_user) {
                    continue; // cap reached
                }

                if ($discount->usage_limit_total && $discount->usage_count >= $discount->usage_limit_total) {
                    continue; // global cap reached
                }

                $userDiscount->increment('usage_count');
                $discount->increment('usage_count');

                event(new DiscountApplied($user, $discount, $before, $final));

                DiscountAudit::create([
                    'user_id' => $user->id,
                    'discount_id' => $discount->id,
                    'action' => 'applied',
                    'meta' => ['before' => $before, 'after' => $final],
                ]);
            }

            return max($final, 0);
        });
    }
}
