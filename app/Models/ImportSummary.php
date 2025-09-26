<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportSummary extends Model
{
    use HasFactory;
    
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
