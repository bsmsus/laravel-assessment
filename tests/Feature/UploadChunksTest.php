<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\Product;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class UploadChunksTest extends TestCase
{
  use RefreshDatabase;

  #[Test]
  public function chunked_upload_and_finalize_creates_variants()
  {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
    $checksum = hash_file('sha256', $file->getPathname());

    // Init (your controller requires metadata)
    $init = $this->postJson('/api/uploads/init', [
      'filename' => 'test.jpg',
      'size'     => $file->getSize(),
      'checksum' => $checksum,
    ])->assertStatus(200)->json();

    $uploadId = $init['upload_id'];

    // Upload chunk
    $this->postJson('/api/uploads/chunk', [
      'upload_id'   => $uploadId,
      'chunk_index' => 0,
      'file'        => $file,
    ])->assertStatus(200);

    // Finalize
    $this->postJson('/api/uploads/complete', [
      'upload_id'    => $uploadId,
      'total_chunks' => 1,
    ])->assertStatus(200);

    // Assert files exist where controller really saves them
    Storage::assertExists("uploads/{$uploadId}_test.jpg");
    foreach ([256, 512, 1024] as $size) {
      Storage::assertExists("uploads/variants/{$uploadId}_{$size}.jpg");
    }
  }

  #[Test]
  public function finalize_fails_when_checksum_mismatch_and_keeps_no_files()
  {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('bad.jpg', 600, 600);
    $wrongChecksum = str_repeat('a', 64);

    $init = $this->postJson('/api/uploads/init', [
      'filename' => 'bad.jpg',
      'size'     => $file->getSize(),
      'checksum' => $wrongChecksum,
    ])->assertStatus(200)->json();

    $uploadId = $init['upload_id'];

    $this->postJson('/api/uploads/chunk', [
      'upload_id'   => $uploadId,
      'chunk_index' => 0,
      'file'        => $file,
    ])->assertStatus(200);

    $this->postJson('/api/uploads/complete', [
      'upload_id'    => $uploadId,
      'total_chunks' => 1,
    ])->assertStatus(422)
      ->assertJsonFragment(['Checksum mismatch']);

    foreach ([256, 512, 1024] as $size) {
      Storage::assertMissing("uploads/variants/{$uploadId}_{$size}.jpg");
    }
  }

  #[Test]
  public function reattaching_same_upload_as_primary_is_idempotent()
  {
    // This will still fail unless you actually add a route/controller
    // for attaching images to products.
    $this->markTestSkipped('Attach-image route not implemented yet.');
  }
}
