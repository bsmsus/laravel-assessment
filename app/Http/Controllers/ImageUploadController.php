<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Upload;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Cache;

class ImageUploadController extends Controller
{

    public function __construct()
    {

        Storage::makeDirectory('chunks');
        Storage::makeDirectory('uploads');
        Storage::makeDirectory('uploads/variants');
    }

    public function init(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'size'     => 'required|integer',
        
        ]);
        $cleanName = $this->sanitizeFilename($request->filename);

        $upload = Upload::create([
            'upload_id' => Str::uuid(),
            'filename'  => $cleanName . '.' . pathinfo($request->filename, PATHINFO_EXTENSION),
            'size'      => $request->size,
            'checksum'  => $request->checksum,
            'status'    => 'initiated',
        ]);

        return response()->json(['upload_id' => $upload->upload_id]);
    }

    public function chunk(Request $request)
    {
        $request->validate([
            'upload_id'   => 'required|uuid',
            'chunk_index' => 'required|integer',
            'file'        => 'required|file',
        ]);

        $path = "chunks/{$request->upload_id}";
        $request->file('file')->storeAs($path, $request->chunk_index, 'public');

        return response()->json(['status' => 'chunk_received']);
    }

    public function complete(Request $request)
    {
        $request->validate([
            'upload_id'    => 'required|uuid',
            'total_chunks' => 'required|integer',
        ]);

        $upload = Upload::where('upload_id', $request->upload_id)->firstOrFail();

        if ($upload->status === 'completed') {
            return $this->details($upload->upload_id);
        }

        $finalPath = "uploads/{$upload->upload_id}_{$upload->filename}";
        $chunkDir  = "chunks/{$upload->upload_id}";

        return Cache::lock("upload:{$upload->upload_id}", 30)
            ->block(10, function () use ($request, $upload, $finalPath, $chunkDir) {
                $chunkFiles = Storage::files($chunkDir);
                $uploadedIndices = array_map('basename', $chunkFiles);

                $missing = [];
                for ($i = 0; $i < $request->total_chunks; $i++) {
                    if (!in_array((string) $i, $uploadedIndices, true)) {
                        $missing[] = $i;
                    }
                }
                if (!empty($missing)) {
                    return response()->json([
                        'error' => 'Missing chunks',
                        'missing_chunks' => $missing,
                    ], 422);
                }

                $output = fopen(Storage::path($finalPath), 'wb');
                foreach (range(0, $request->total_chunks - 1) as $i) {
                    $chunkStream = Storage::readStream("{$chunkDir}/{$i}");
                    stream_copy_to_stream($chunkStream, $output);
                    fclose($chunkStream);
                }
                fclose($output);

                $ctx = hash_init('sha256');
                $fp  = fopen(Storage::path($finalPath), 'rb');
                hash_update_stream($ctx, $fp);
                fclose($fp);
                $actualChecksum = hash_final($ctx);

                if ($actualChecksum !== $upload->checksum) {
                    Storage::delete($finalPath);
                    return response()->json(['error' => 'Checksum mismatch'], 422);
                }

                foreach ([256, 512, 1024] as $size) {
                    $img = Image::read(Storage::path($finalPath))
                        ->resize($size, $size, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });

                    $variantPath = "uploads/variants/{$upload->upload_id}_{$size}.jpg";
                    $img->save(Storage::path($variantPath));
                }

                Storage::deleteDirectory($chunkDir);

                $upload->update(['status' => 'completed']);

                return response()->json([
                    'status'   => 'upload_complete',
                    'variants' => [
                        '256'  => Storage::url("uploads/variants/{$upload->upload_id}_256.jpg"),
                        '512'  => Storage::url("uploads/variants/{$upload->upload_id}_512.jpg"),
                        '1024' => Storage::url("uploads/variants/{$upload->upload_id}_1024.jpg"),
                    ]
                ]);
            });
    }


    public function status($uploadId)
    {
        $upload = Upload::where('upload_id', $uploadId)->firstOrFail();

        $chunkFiles = Storage::files("chunks/{$uploadId}");
        $uploadedIndices = array_map('basename', $chunkFiles);

        return response()->json([
            'upload_id'       => $uploadId,
            'uploaded_chunks' => $uploadedIndices,
            'status'          => $upload->status,
        ]);
    }

    public function details($uploadId)
    {
        $upload = Upload::where('upload_id', $uploadId)->firstOrFail();

        $variants = [];
        if ($upload->status === 'completed') {

            $variants = [
                '256'  => Storage::url("uploads/variants/{$upload->upload_id}_256.jpg"),
                '512'  => Storage::url("uploads/variants/{$upload->upload_id}_512.jpg"),
                '1024' => Storage::url("uploads/variants/{$upload->upload_id}_1024.jpg"),
            ];
        }

        return response()->json([
            'upload_id' => $upload->upload_id,
            'filename'  => $upload->filename,
            'status'    => $upload->status,
            'variants'  => $variants,
        ]);
    }

    private function sanitizeFilename($name)
    {
        $name = pathinfo($name, PATHINFO_FILENAME);
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
        return Str::lower($name);
    }
}
