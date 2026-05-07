<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionExpense extends Model
{
    use HasFactory;

    /**
     * Get the missions that owns the MissionExpense
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function missions(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
