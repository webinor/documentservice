<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicHoliday extends Model
{

    use HasFactory;


    protected $table = 'public_holidays';

    protected $fillable = [
        'work_calendar_id',
        'date',
        'name',
        'counts_for_leave',
        'is_recurring',
        'description',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'counts_for_leave' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    /**
     * Calendrier de travail associé.
     */
    public function workCalendar(): BelongsTo
    {
        return $this->belongsTo(
            WorkCalendar::class,
            'work_calendar_id'
        );
    }
}