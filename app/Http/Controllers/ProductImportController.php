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
            'file' => 'required|file|mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel|max:10240',
        ]);

        // Store file temporarily
        $path = $request->file('file')->store('imports');

        // Create summary row immediately
        $importSummary = ImportSummary::create([
            'file_path'  => $path,
            'total'      => 0,
            'imported'   => 0,
            'updated'    => 0,
            'invalid'    => 0,
            'duplicates' => 0,
            'status'     => 'processing',
        ]);

        // Dispatch job, pass ID so it can update instead of creating new row
        ProcessProductImport::dispatch($path, $importSummary->id);

        return response()->json([
            'message' => 'File uploaded. Import is being processed.',
            'summary_id' => $importSummary->id,
        ]);
    }

    public function summary($id)
    {
        return ImportSummary::findOrFail($id);
    }
}
