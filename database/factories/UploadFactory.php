<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Upload;

class UploadFactory extends Factory
{
    protected $model = Upload::class;

    public function definition(): array
    {
        return [
            'file_path' => 'uploads/'.$this->faker->uuid.'.jpg',
            'checksum'  => $this->faker->sha256,
            'status'    => 'completed',
        ];
    }
}
