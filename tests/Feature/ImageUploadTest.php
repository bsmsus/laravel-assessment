<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_merges_chunks_and_generates_variants()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('test.png', 1200, 800);

        $ctx = hash_init('sha256');
        $fp  = fopen(Storage::path($file->getRealPath()), 'rb');
        hash_update_stream($ctx, $fp);
        fclose($fp);
        $checksum = hash_final($ctx);


        // Init
        $init = $this->postJson('/api/uploads/init', [
            'filename' => 'test.png',
            'size'     => $file->getSize(),
            'checksum' => $checksum,
        ])->assertStatus(200)
            ->json();

        $uploadId = $init['upload_id'];
        $this->assertNotEmpty($uploadId);

        // Upload chunk (single-chunk case)
        $this->postJson('/api/uploads/chunk', [
            'upload_id'   => $uploadId,
            'chunk_index' => 0,
            'file'        => $file,
        ])->assertStatus(200);

        // Complete
        $this->postJson('/api/uploads/complete', [
            'upload_id'    => $uploadId,
            'total_chunks' => 1,
        ])->assertStatus(200)
            ->assertJson(['status' => 'upload_complete']);

        // Assert original + variants
        Storage::assertExists("uploads/test.png");
        foreach ([256, 512, 1024] as $size) {
            Storage::assertExists("uploads/variants/{$size}_test.png");
        }
    }

    #[Test]
    public function it_fails_if_chunks_are_missing()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('broken.png', 800, 600);
        $checksum = hash_file('sha256', $file->getRealPath());

        // Init
        $init = $this->postJson('/api/uploads/init', [
            'filename' => 'broken.png',
            'size'     => $file->getSize(),
            'checksum' => $checksum,
        ])->assertStatus(200)
            ->json();

        $uploadId = $init['upload_id'];

        // Upload NO chunks at all

        // Try to complete (expect failure)
        $this->postJson('/api/uploads/complete', [
            'upload_id'    => $uploadId,
            'total_chunks' => 2, // claim 2 chunks but uploaded 0
        ])->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'missing_chunks',
            ]);
    }

    #[Test]
    public function it_fails_if_checksum_does_not_match()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('fake.png', 600, 400);
        $wrongChecksum = str_repeat('a', 64); // totally wrong SHA256

        // Init with wrong checksum
        $init = $this->postJson('/api/uploads/init', [
            'filename' => 'fake.png',
            'size'     => $file->getSize(),
            'checksum' => $wrongChecksum,
        ])->assertStatus(200)
            ->json();

        $uploadId = $init['upload_id'];

        // Upload single chunk
        $this->postJson('/api/uploads/chunk', [
            'upload_id'   => $uploadId,
            'chunk_index' => 0,
            'file'        => $file,
        ])->assertStatus(200);

        // Complete (should fail checksum)
        $this->postJson('/api/uploads/complete', [
            'upload_id'    => $uploadId,
            'total_chunks' => 1,
        ])->assertStatus(422)
            ->assertJson(['error' => 'Checksum mismatch']);
    }
}
