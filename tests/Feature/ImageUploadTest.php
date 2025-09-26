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
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test.png', 1200, 800);
        $checksum = hash_file('sha256', $file->getPathname());

        // Init
        $init = $this->postJson('/api/uploads/init', [
            'filename' => 'test.png',
            'size'     => $file->getSize(),
            'checksum' => $checksum,
        ])->assertStatus(200)
            ->json();

        $uploadId = $init['upload_id'];
        $this->assertNotEmpty($uploadId);

        // Upload chunk
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

        // Assertions (aligned to actual paths)
        Storage::assertExists("uploads/{$uploadId}_test.png");
        foreach ([256, 512, 1024] as $size) {
            Storage::assertExists("uploads/variants/{$uploadId}_{$size}.jpg");
        }
    }


    #[Test]
    public function it_fails_if_chunks_are_missing()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('broken.png', 800, 600);
        $checksum = hash_file('sha256', $file->getPathname());

        $init = $this->postJson('/api/uploads/init', [
            'filename' => 'broken.png',
            'size'     => $file->getSize(),
            'checksum' => $checksum,
        ])->assertStatus(200)
            ->json();

        $uploadId = $init['upload_id'];

        // No chunks uploaded, try complete
        $this->postJson('/api/uploads/complete', [
            'upload_id'    => $uploadId,
            'total_chunks' => 2,
        ])->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'missing_chunks',
            ]);
    }

    #[Test]
    public function it_fails_if_checksum_does_not_match()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('fake.png', 600, 400);
        $wrongChecksum = str_repeat('a', 64);

        $init = $this->postJson('/api/uploads/init', [
            'filename' => 'fake.png',
            'size'     => $file->getSize(),
            'checksum' => $wrongChecksum,
        ])->assertStatus(200)
            ->json();

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
    }
}
