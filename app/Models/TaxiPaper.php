<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxiPaper extends Model
{
    use HasFactory;


    protected $fillable = [
        'document_id',
        'reason',
        'rides',
        'beneficiary',
    ];

    protected $casts = [
        'rides' => 'array',
    ];
}
