<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxiRegulation extends Model
{
    use HasFactory;

     protected $fillable = [
        'taxi_paper_id',
        'amount',
        'paid_at',
        'transaction_code',
        'comment',
        'type',
        'status',
        'created_by',
        'validated_by',
        'validated_at',
        'transaction_id'
    ];

     protected $casts = [
        'paid_at' => 'datetime',
        'validated_at' => 'datetime',
        'amount' => 'decimal:2'
    ];
}
