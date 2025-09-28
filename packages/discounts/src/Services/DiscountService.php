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
        if (!$discount->isActive()) {
            return;
        }

        UserDiscount::firstOrCreate(
            ['user_id' => $user->id, 'discount_id' => $discount->id],
            ['usage_count' => 0]
        );

        event(new DiscountAssigned($user, $discount));

        DiscountAudit::create([
            'user_id'     => $user->id,
            'discount_id' => $discount->id,
            'action'      => 'assigned',
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
                'user_id'     => $user->id,
                'discount_id' => $discount->id,
                'action'      => 'revoked',
            ]);
        }
    }

    public function apply(User $user, float $amount): float
    {
        return DB::transaction(function () use ($user, $amount) {
            $discounts = Discount::whereHas('userDiscounts', function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNull('revoked_at');
            })->get();

            $eligible = $discounts->filter(fn($d) => $d->isActive());

            $stackingOrder = config('discounts.stacking_order', ['percentage', 'fixed']);

            $eligible      = $eligible->sortBy(fn($d) => array_search($d->type, $stackingOrder));

            $final = $amount;

            foreach ($eligible as $discount) {
                $userDiscount = UserDiscount::where('user_id', $user->id)
                    ->where('discount_id', $discount->id)
                    ->lockForUpdate()
                    ->first();

                // ----- usage limit checks BEFORE applying -----
                if (
                    $discount->usage_cap !== null &&
                    $userDiscount->usage_count >= $discount->usage_cap
                ) {
                    continue;
                }

                if (
                    $discount->usage_limit_per_user !== null &&
                    $userDiscount->usage_count >= $discount->usage_limit_per_user
                ) {
                    continue;
                }

                if (
                    $discount->usage_limit_total !== null &&
                    $discount->usage_count >= $discount->usage_limit_total
                ) {
                    continue;
                }


                // ----- apply discount -----
                $before = $final;

                if ($discount->type === 'percentage') {
                    $cap   = config('discounts.max_percentage_cap', 100);
                    $value = min($discount->value, $cap);
                    $final -= ($final * $value / 100);
                } else {
                    $final -= $discount->value;
                }

                $rounding = config('discounts.rounding', fn($v) => round($v, 2));
                $final    = $rounding($final);

                // ----- increment usage counts -----
                $userDiscount->increment('usage_count');
                $discount->increment('usage_count');

                event(new DiscountApplied($user, $discount, $before, $final));

                DiscountAudit::create([
                    'user_id'     => $user->id,
                    'discount_id' => $discount->id,
                    'action'      => 'applied',
                    'meta'        => ['before' => $before, 'after' => $final],
                ]);
            }

            return max($final, 0);
        });
    }

    public function eligibleFor(User $user)
    {
        return $user->discounts()
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }
}
