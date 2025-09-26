<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku'   => $this->faker->unique()->ean8,
            'name'  => $this->faker->words(3, true),
            'price' => $this->faker->numberBetween(100, 1000),
        ];
    }
}
