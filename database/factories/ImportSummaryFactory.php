<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ImportSummary;

class ImportSummaryFactory extends Factory
{
    protected $model = ImportSummary::class;

    public function definition(): array
    {
        return [
            'file_path' => 'imports/'.$this->faker->uuid.'.csv',
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'status' => 'processing',
        ];
    }
}
