<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class UploadChunksTest extends TestCase
{
  use RefreshDatabase;

  /** @test */
  public function chunked_upload_and_finalize_creates_variants_and_links_primary_image()
  {
    Storage::fake('public');

    // Create a dummy image (say 1200x800) using Intervention or GD
    $img = imagecreatetruecolor(1200, 800);
    ob_start();
    imagejpeg($img, null, 90);
    $jpeg = ob_get_clean();

    $file = UploadedFile::fake()->createWithContent('test.jpg', $jpeg);
    $checksum = hash_file('sha256', $file->getPathname());

    // Simulate chunk upload: break into chunks
    $chunkSize = 500 * 1024; // 500 KB
    $content = file_get_contents($file->getPathname());
    $total = strlen($content);

    // Start upload session (your API should return upload_id)
    $resp = $this->postJson('/api/uploads', []);
    $uploadId = $resp->json('upload_id');

    $offset = 0;
    while ($offset < $total) {
      $chunk = substr($content, $offset, $chunkSize);
      $size = strlen($chunk);

      $start = $offset;
      $end = $offset + $size - 1;
      $contentRange = "bytes $start-$end/$total";

      $this->withHeaders([
        'Content-Range' => $contentRange,
        'X-Upload-Checksum' => $checksum,
      ])->putJson("/api/uploads/{$uploadId}/chunk", ['chunk' => base64_encode($chunk)]);

      $offset += $size;
    }

    // Finalize
    $final = $this->postJson("/api/uploads/{$uploadId}/complete", [
      'checksum' => $checksum
    ]);
    $final->assertStatus(200);

    // Now, assert variant files exist
    Storage::disk('public')->assertExists("images/{$uploadId}_256.jpg");
    Storage::disk('public')->assertExists("images/{$uploadId}_512.jpg");
    Storage::disk('public')->assertExists("images/{$uploadId}_1024.jpg");

    // Create Product and attach this upload as primary
    $product = Product::factory()->create();
    $this->postJson("/api/products/{$product->id}/attach-image", [
      'upload_id' => $uploadId
    ])->assertStatus(200);

    // Assert product primary_image_id is set
    $product->refresh();
    $this->assertNotNull($product->primary_image_id);
  }

  /** @test */
  public function finalize_fails_when_checksum_mismatch_and_keeps_no_files()
  {
    Storage::fake('public');

    // Create a dummy image (say 1200x800) using Intervention or GD
    $img = imagecreatetruecolor(1200, 800);
    ob_start();
    imagejpeg($img, null, 90);
    $jpeg = ob_get_clean();

    $file = UploadedFile::fake()->createWithContent('test.jpg', $jpeg);
    $checksum = hash_file('sha256', $file->getPathname());

    // Simulate chunk upload: break into chunks
    $chunkSize = 500 * 1024; // 500 KB
    $content = file_get_contents($file->getPathname());
    $total = strlen($content);

    $resp = $this->postJson('/api/uploads', []);
    $uploadId = $resp->json('upload_id');

    $offset = 0;
    while ($offset < $total) {
      $chunk = substr($content, $offset, $chunkSize);
      $size = strlen($chunk);

      $start = $offset;
      $end = $offset + $size - 1;
      $contentRange = "bytes $start-$end/$total";

      $this->withHeaders([
        'Content-Range' => $contentRange,
        'X-Upload-Checksum' => $checksum,
      ])->putJson("/api/uploads/{$uploadId}/chunk", ['chunk' => base64_encode($chunk)]);

      $offset += $size;
    }

    $wrongChecksum = 'deadbeef';
    $resp = $this->postJson("/api/uploads/{$uploadId}/complete", [
      'checksum' => $wrongChecksum
    ]);
    $resp->assertStatus(422);
    $this->assertStringContainsString('checksum mismatch', $resp->json('message'));

    Storage::disk('public')->assertMissing("images/{$uploadId}_256.jpg");
    Storage::disk('public')->assertMissing("images/{$uploadId}_512.jpg");
    Storage::disk('public')->assertMissing("images/{$uploadId}_1024.jpg");
  }

  /** @test */
  public function reattaching_same_upload_as_primary_is_idempotent()
  {
    $product = Product::factory()->create();
    $upload = Upload::factory()->create();

    $this->postJson("/api/products/{$product->id}/attach-image", ['upload_id' => $upload->id])
      ->assertStatus(200);
    $firstPrimary = $product->refresh()->primary_image_id;

    // Re-attach same upload
    $this->postJson("/api/products/{$product->id}/attach-image", ['upload_id' => $upload->id])
      ->assertStatus(200);
    $secondPrimary = $product->refresh()->primary_image_id;

    $this->assertEquals($firstPrimary, $secondPrimary);

    // Ensure no duplicate entries in pivot / join table
    $count = DB::table('imageables')->where([
      'imageable_type' => Product::class,
      'imageable_id' => $product->id,
      'upload_id' => $upload->id
    ])->count();
    $this->assertEquals(1, $count);
  }
}
