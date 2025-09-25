<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Upload;
use Intervention\Image\Laravel\Facades\Image;

class ImageUploadController extends Controller
{
    /**
     * Start a new upload session.
     * Client sends filename, size, checksum.
     * Server returns upload_id (UUID).
     */
    public function init(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'size'     => 'required|integer',
            'checksum' => 'required|string', // SHA256 or MD5
        ]);

        $upload = Upload::create([
            'upload_id' => Str::uuid(),
            'filename'  => $request->filename,
            'size'      => $request->size,
            'checksum'  => $request->checksum,
            'status'    => 'initiated',
        ]);

        return response()->json([
            'upload_id' => $upload->upload_id,
        ]);
    }

    /**
     * Receive a single chunk of the file.
     * Stores it temporarily under storage/app/chunks/{upload_id}/
     */
    public function chunk(Request $request)
    {
        $request->validate([
            'upload_id'   => 'required|uuid',
            'chunk_index' => 'required|integer',
            'file'        => 'required|file',
        ]);

        $path = "chunks/{$request->upload_id}";
        $request->file('file')->storeAs($path, $request->chunk_index);

        return response()->json(['status' => 'chunk_received']);
    }

    /**
     * Merge chunks, verify checksum, and generate variants.
     */
    public function complete(Request $request)
    {
        $request->validate([
            'upload_id'    => 'required|uuid',
            'total_chunks' => 'required|integer',
        ]);

        $upload = Upload::where('upload_id', $request->upload_id)->firstOrFail();

        $finalPath = "uploads/{$upload->filename}";
        $dir = dirname($finalPath);
        Storage::makeDirectory($dir);

        // Step 1: Verify all chunks exist
        $chunkFiles = Storage::files("chunks/{$upload->upload_id}");
        $uploadedIndices = array_map('basename', $chunkFiles);

        $missing = [];
        for ($i = 0; $i < $request->total_chunks; $i++) {
            if (!in_array((string)$i, $uploadedIndices, true)) {
                $missing[] = $i;
            }
        }

        if (!empty($missing)) {
            return response()->json([
                'error' => 'Missing chunks',
                'missing_chunks' => $missing,
            ], 422);
        }

        // Step 2: Merge chunks sequentially
        $output = fopen(Storage::path($finalPath), 'wb');
        foreach (range(0, $request->total_chunks - 1) as $i) {
            fwrite($output, Storage::get("chunks/{$upload->upload_id}/{$i}"));
        }
        fclose($output);

        // Step 3: Verify checksum
        $actualChecksum = hash_file('sha256', Storage::path($finalPath));
        if ($actualChecksum !== $upload->checksum) {
            return response()->json(['error' => 'Checksum mismatch'], 422);
        }

        // Step 4: Generate image variants
        $variantDir = "uploads/variants";
        Storage::makeDirectory($variantDir);

        foreach ([256, 512, 1024] as $size) {
            $img = Image::read(Storage::path($finalPath))
                ->resize($size, $size, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

            $variantPath = "{$variantDir}/{$size}_{$upload->filename}";
            $img->save(Storage::path($variantPath));
        }

        $upload->update(['status' => 'completed']);

        return response()->json(['status' => 'upload_complete']);
    }


    public function status($uploadId)
    {
        // Make sure this upload exists
        $upload = Upload::where('upload_id', $uploadId)->firstOrFail();

        // Get list of uploaded chunk files
        $chunkFiles = Storage::files("chunks/{$uploadId}");

        // Extract just the chunk indices (basename is like "0", "1", "2")
        $uploadedIndices = array_map('basename', $chunkFiles);

        return response()->json([
            'upload_id' => $uploadId,
            'uploaded_chunks' => $uploadedIndices,
            'status' => $upload->status,
        ]);
    }
}
