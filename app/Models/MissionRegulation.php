<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissionRegulation extends Model
{
    use HasFactory;

       protected $fillable = [
        'mission_id',
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
        'paid_at' => 'date',
        'validated_at' => 'datetime',
        'amount' => 'decimal:2'
    ];
}
