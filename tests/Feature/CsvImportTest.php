<?php
namespace Tests\Feature; // or Unit

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function import_endpoint_produces_correct_summary_for_mixed_rows()
    {
        Product::factory()->create([
            'sku' => 'SKU_EXIST',
            'name' => 'OldName',
            'price' => 100
        ]);

        $csv = <<<CSV
sku,name,price
SKU_EXIST,NewName,150
SKU_NEW,FirstName,200
BAD_ROW
SKU_NEW,DuplicateRow,300
CSV;

        $encoded = base64_encode($csv);
        $response = $this->postJson('/api/import-products', ['csv' => $encoded]);
        $response->assertStatus(200);

        $result = $response->json('result');
        $this->assertEquals(4, $result['total']);
        $this->assertEquals(1, $result['imported']);    // SKU_NEW first time
        $this->assertEquals(1, $result['updated']);     // SKU_EXIST updated
        $this->assertEquals(2, $result['invalid']);     // BAD_ROW + duplicate of SKU_NEW
    }
}
