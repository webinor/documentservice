<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsenceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
                'reason' ,
                'beneficiary',
                'departure_date',
                'departure_time',
                'return_date',
                'return_time',
                'duties_handover',
                'handover_details',
                'document_id',
                'leave_type_id'
    ];



    protected $appends = [
        "duration"
    ];


    public function getDurationAttribute()
    {
        if (!$this->departure_date || !$this->return_date) {
            return 0;
        }

        return Carbon::parse($this->departure_date)
            ->diffInDays(
                Carbon::parse($this->return_date)
            ) + 1;
    }


        public function getDepartureDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}

       public function getReturnDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}

/**
 * Get the leave_type that owns the AbsenceRequest
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
public function leave_type(): BelongsTo
{
    return $this->belongsTo(LeaveType::class);
}

/**
 * Get the leaveRequestDays that owns the AbsenceRequest
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
public function leaveRequestDays(): HasMany
{
    return $this->hasMany(LeaveRequestDay::class);
}


}
