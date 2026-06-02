<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissionAdvance extends Model
{
    use HasFactory;

     protected $fillable = [
        'mission_id',
        'amount',
        'payment_date',
        'reference',
        'comment',
        'status',
        'created_by',
        'validated_by',
        'validated_at',
        'transaction_id'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'validated_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

   
}
