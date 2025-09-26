<?php
namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product; // or User
use Illuminate\Http\UploadedFile;
use Storage;

class UpsertImportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function csv_upsert_creates_and_updates_and_reports_summary()
    {
        // existing record to be updated
        Product::factory()->create(['sku' => 'SKU-EXIST', 'name' => 'Old']);

        $csv = <<<CSV
sku,name,price
SKU-EXIST,Updated Name,150
SKU-NEW,New Product,200
,MissingSKU,10
CSV;

        // call your import endpoint or artisan command that processes CSV from string
        $response = $this->postJson('/api/import-products', [
            'csv' => base64_encode($csv)
        ]);

        $response->assertStatus(200);
        $result = $response->json('result');

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, $result['invalid']);
    }
}
