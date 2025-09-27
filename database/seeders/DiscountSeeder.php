<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Rahul\Discounts\Models\Discount;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        Discount::firstOrCreate(
            ['name' => 'Festive 20%'],
            [
                'type' => 'percent',
                'value' => 20,
                'active' => true,
                'usage_limit_per_user' => 3,
            ]
        );

        Discount::firstOrCreate(
            ['name' => 'Flat 500 Off'],
            [
                'type' => 'flat',
                'value' => 500,
                'active' => true,
            ]
        );
    }
}
