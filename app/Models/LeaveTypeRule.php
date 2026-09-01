<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveTypeRule extends Model
{
    use HasFactory;


   protected $casts = [
    'settings' => 'array',

    'paid_days' => 'integer',

    'max_days' => 'integer',

    'deduct_excess_days' => 'boolean',

    'uses_balance' => 'boolean',

    'allow_split' => 'boolean',
];
}
