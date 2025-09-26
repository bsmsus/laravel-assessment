<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportSummary extends Model
{
    protected $fillable = [
        'total',
        'imported',
        'updated',
        'invalid',
        'duplicates',
        'status',
        'file_path'
    ];
}
