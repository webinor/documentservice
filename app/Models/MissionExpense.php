<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionExpense extends Model
{
    use HasFactory;


     protected $fillable = [
        'mission_id',
        'expense_category_id',
        'amount',
        'expense_date',
        'description',
        'receipt_path',
        'is_validated',
        'type',
        'quantity'
    ];

    /**
     * Get the missions that owns the MissionExpense
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function missions(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * Get the expense_category that owns the MissionExpense
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expense_category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }


           public function getAmountAttribute($value)
{
    if (!$value ) {
        return null; 
    }
    return (int)$value;
}
}
