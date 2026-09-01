<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCalendarRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_calendar_id',
        'type',
        'day_of_week',
        'reference_date',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];


    public function workCalendar(): BelongsTo
    {
        return $this->belongsTo(
            WorkCalendar::class
        );
    }
}
