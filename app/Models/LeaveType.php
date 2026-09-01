<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LeaveType extends Model
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

    /**
     * Get the rule associated with the LeaveType
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function rule(): HasOne
    {
        return $this->hasOne(LeaveTypeRule::class);
    }
}
