<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LeaveType extends Model
{
    use HasFactory;

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
