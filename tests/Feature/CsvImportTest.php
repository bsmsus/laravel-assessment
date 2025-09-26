<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Models\Product;
use App\Models\ImportSummary;
use App\Jobs\ProcessProductImport;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;

class CsvImportTest extends TestCase
{
  use RefreshDatabase;

  #[Test]
  public function it_stores_file_and_creates_import_summary_and_dispatches_job()
  {
    Bus::fake();

    // existing product for update
    Product::factory()->create([
      'sku' => 'SKU_EXIST',
      'name' => 'OldName',
      'price' => 100
    ]);

    $csv = <<<CSV
sku,name,price
SKU_EXIST,NewName,150
SKU_NEW,FirstName,200
,MissingSKU,10
SKU_NEW,DuplicateRow,300
CSV;

    $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

    // phase 1: upload/import
    $resp = $this->postJson('/api/products/import', ['file' => $file]);

    $resp->assertStatus(200);
    $resp->assertJsonStructure([
      'message',
      'summary_id',
    ]);

    $summaryId = $resp->json('summary_id');

    // assert summary record created
    $this->assertDatabaseHas('import_summaries', [
      'id' => $summaryId,
      'status' => 'processing',
    ]);

    // assert job dispatched
    Bus::assertDispatched(ProcessProductImport::class, function ($job) use ($summaryId) {
      return $job->getSummaryId() === $summaryId;
    });
  }

  #[Test]
  public function it_returns_summary_counts_after_processing()
  {
    // seed a finished ImportSummary row (simulate job finished)
    $summary = ImportSummary::factory()->create([
      'total' => 4,
      'imported' => 1,
      'updated' => 1,
      'invalid' => 1,
      'duplicates' => 1,
      'status' => 'completed',
    ]);

    $resp = $this->getJson("/api/products/import/summary/{$summary->id}");
    $resp->assertStatus(200);

    $resp->assertJson([
      'id' => $summary->id,
      'total' => 4,
      'imported' => 1,
      'updated' => 1,
      'invalid' => 1,
      'duplicates' => 1,
      'status' => 'completed',
    ]);
  }
}
