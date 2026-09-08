<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCalendar extends Model
{
    use HasFactory;

    protected $table = 'work_calendars';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Jours fériés associés au calendrier.
     */
    public function publicHolidays(): HasMany
    {
        return $this->hasMany(
            PublicHoliday::class,
            'work_calendar_id'
        );
    }

        public function rules(): HasMany
{
    return $this->hasMany(
        WorkCalendarRule::class
    );
}
}