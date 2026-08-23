<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularizationItemReceipt extends Model
{
    use HasFactory;

    protected $casts = [
        'allocated_amount'=>'integer'
    ];
}
