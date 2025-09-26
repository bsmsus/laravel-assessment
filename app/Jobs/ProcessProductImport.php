<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Upload;
use App\Models\ImportSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Storage;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $path, protected int $summaryId) {}
    public function getSummaryId(): int
    {
        return $this->summaryId;
    }

    public function handle()
    {
        // Load existing summary row
        $importSummary = ImportSummary::findOrFail($this->summaryId);

        $summary = [
            'total'      => 0,
            'imported'   => 0,
            'updated'    => 0,
            'invalid'    => 0,
            'duplicates' => 0,
        ];

        $seen = [];
        $batch = [];

        LazyCollection::make(function () {
            $handle = fopen(Storage::path($this->path), 'r');
            while (($line = fgets($handle)) !== false) {
                yield $line;
            }
            fclose($handle);
        })
            ->skip(1)
            ->each(function ($line) use (&$summary, &$seen, &$batch, $importSummary) {
                $summary['total']++;
                $columns = str_getcsv($line);
                [$sku, $name, $price, $imageFilename] = array_pad($columns, 4, null);

                if (!$sku || !$name) {
                    $summary['invalid']++;
                    return;
                }

                if (isset($seen[$sku])) {
                    $summary['duplicates']++;
                    return;
                }
                $seen[$sku] = true;

                $upload = $imageFilename
                    ? Upload::where('filename', $imageFilename)->where('status', 'completed')->first()
                    : null;

                $batch[] = [
                    'sku'        => $sku,
                    'name'       => $name,
                    'price'      => $price,
                    'image_path' => $upload ? "uploads/{$upload->upload_id}_{$upload->filename}" : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($summary['total'] % 100 === 0) {
                    $this->flushBatch($batch, $summary, $importSummary);
                    $batch = [];
                }
            });

        if (!empty($batch)) {
            $this->flushBatch($batch, $summary, $importSummary);
        }

        $importSummary->update(array_merge($summary, ['status' => 'completed']));
    }

    private function flushBatch(array &$batch, array &$summary, ImportSummary $importSummary): void
    {
        if (empty($batch)) return;

        $skus = array_column($batch, 'sku');
        $existing = Product::whereIn('sku', $skus)->pluck('sku')->toArray();

        foreach ($batch as $row) {
            if (in_array($row['sku'], $existing)) {
                $summary['updated']++;
            } else {
                $summary['imported']++;
            }
        }

        Product::upsert(
            $batch,
            ['sku'],
            ['name', 'price', 'image_path', 'updated_at']
        );

        $importSummary->update($summary);
    }

    public function failed(\Throwable $exception)
    {
        ImportSummary::where('id', $this->summaryId)->update([
            'status' => 'failed',
        ]);
    }
}
