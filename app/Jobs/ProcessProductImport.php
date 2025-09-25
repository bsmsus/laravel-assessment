<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ImportSummary;


class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        $handle = fopen(Storage::path($this->filePath), 'r');
        $header = fgetcsv($handle);

        $summary = [
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'invalid' => 0,
            'duplicates' => 0,
        ];

        $seenSkus = [];

        while (($row = fgetcsv($handle)) !== false) {
            $summary['total']++;

            $data = array_combine($header, $row);

            // Validation
            if (empty($data['sku']) || empty($data['name']) || empty($data['price'])) {
                $summary['invalid']++;
                continue;
            }

            // Track duplicates within same file
            if (in_array($data['sku'], $seenSkus)) {
                $summary['duplicates']++;
                continue;
            }
            $seenSkus[] = $data['sku'];

            // Upsert
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'price' => $data['price'],
                ]
            );

            if ($product->wasRecentlyCreated) {
                $summary['imported']++;
            } else {
                $summary['updated']++;
            }
        }

        fclose($handle);

        // Save summary to DB
        ImportSummary::create(array_merge($summary, [
            'file_path' => $this->filePath,
        ]));
    }
}
