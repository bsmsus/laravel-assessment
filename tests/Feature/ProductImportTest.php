<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_upserts_products_by_sku()
    {
        // Arrange: an existing product
        $existing = Product::create([
            'sku' => 'SKU1001',
            'name' => 'Old Name',
            'description' => 'Old desc',
            'price' => 100,
        ]);

        // Simulate new CSV rows
        $rows = [
            ['sku' => 'SKU1001', 'name' => 'New Name', 'description' => 'Updated', 'price' => 200],
            ['sku' => 'SKU2001', 'name' => 'Brand New', 'description' => 'Fresh', 'price' => 300],
        ];

        foreach ($rows as $data) {
            Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                ]
            );
        }

        // Assert: existing was updated
        $this->assertDatabaseHas('products', [
            'sku' => 'SKU1001',
            'name' => 'New Name',
            'price' => 200,
        ]);

        // Assert: new one inserted
        $this->assertDatabaseHas('products', [
            'sku' => 'SKU2001',
            'name' => 'Brand New',
            'price' => 300,
        ]);
    }
}
