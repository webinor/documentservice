<?php

namespace App\Models;

use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination',
        'start_date',
        'end_date',
        'estimated_budget',
        'advance_amount',
        'is_special',
        'actor_type',
        'actor_id',
        'document_id',
    ];

    public function document()
{
    return $this->belongsTo(Document::class);
}

/**
 * Get all of the missions_expenses for the Mission
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
public function mission_expenses(): HasMany
{
    return $this->hasMany(MissionExpense::class);
}


        public function getStartDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}

       public function getEndDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}
}
