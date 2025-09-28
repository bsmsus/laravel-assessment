<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Rahul\Discounts\Models\Discount;
use Rahul\Discounts\Services\DiscountService;

class DiscountController extends Controller
{
    protected $service;

    public function __construct(DiscountService $service)
    {
        $this->service = $service;
    }

    // Assign discount to a user
    public function assign(Request $request, $userId, $discountId)
    {
        $user = User::findOrFail($userId);
        $discount = Discount::findOrFail($discountId);

        $this->service->assign($user, $discount);

        return response()->json([
            'message' => "Discount '{$discount->name}' assigned to user {$user->id}"
        ]);
    }

    // Revoke discount from a user
    public function revoke(Request $request, $userId, $discountId)
    {
        $user = User::findOrFail($userId);
        $discount = Discount::findOrFail($discountId);

        $this->service->revoke($user, $discount);

        return response()->json([
            'message' => "Discount '{$discount->name}' revoked from user {$user->id}"
        ]);
    }

    // Apply discounts to an amount
    public function apply(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $amount = (float) $request->query('amount', 1000); // default 1000 if not passed

        $final = $this->service->apply($user, $amount);

        return response()->json([
            'user_id' => $user->id,
            'original' => $amount,
            'final' => $final
        ]);
    }

    public function eligibleFor(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $discounts = $this->service->eligibleFor($user);

        return response()->json([
            'user_id' => $user->id,
            'eligible_discounts' => $discounts
        ]);
    }
}
