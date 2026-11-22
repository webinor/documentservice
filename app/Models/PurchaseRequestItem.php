<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'requested_quantity',
        'approved_quantity',

        'requested_amount',
        'approved_amount',


        'specification',
        'purchase_request_id',
        'notice',

        'is_ok'
        
    ];

    protected $casts = [
    'is_ok' => 'boolean',
];
}
