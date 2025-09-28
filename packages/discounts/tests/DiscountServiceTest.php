<?php

namespace Rahul\Discounts\Tests;

use Orchestra\Testbench\TestCase;
use App\Models\User;
use Rahul\Discounts\Models\Discount;
use Rahul\Discounts\Services\DiscountService;
use Rahul\Discounts\DiscountServiceProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;

class DiscountServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function getPackageProviders($app)
    {
        return [
            DiscountServiceProvider::class,
        ];
    }
    
    #[Test]
    public function it_applies_a_percentage_discount_and_respects_usage_cap()
    {
        $user = User::factory()->create();

        $discount = Discount::create([
            'name'      => 'Test 10% off',
            'type'      => 'percentage',
            'value'     => 10,
            'usage_cap' => 1,
            'active'    => true,
        ]);

        $service = new DiscountService();

        $service->assign($user, $discount);

        $this->assertEquals(90, $service->apply($user, 100));
        $this->assertEquals(100, $service->apply($user, 100));
    }
}
