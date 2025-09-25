<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessProductImport;
use Illuminate\Support\Facades\Storage;
use App\Models\ImportSummary;


class ProductImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240', // 10MB max
        ]);

        // Store file temporarily
        $path = $request->file('file')->store('imports');

        // Dispatch job to process file
        ProcessProductImport::dispatch($path);

        return response()->json([
            'message' => 'File uploaded. Import is being processed.',
            'file' => $path,
        ]);
    }
    public function summary()
    {
        return ImportSummary::latest()->first();
    }
}
