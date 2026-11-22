<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MissionExpense extends Model
{
    use HasFactory;


     protected $fillable = [
          'mission_id',
    'expense_category_id',
    'amount',
    'total', // ✅ AJOUT
    'expense_date',
    'description',
    'receipt_path',
    'is_validated',
    'type',
    'comment',
    'quantity',
    // 'planned_quantity',
    // 'actual_quantity',
    ];

    public function getTotalAttribute()
{
    return $this->amount * ($this->quantity);
}

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
 * Justificatifs associés à cette dépense.
 */
public function receipts(): BelongsToMany
{
    return $this->belongsToMany(
        MissionReceipt::class,
        'mission_expense_receipt',
        'mission_expense_id',
        'mission_receipt_id'
    )
    ->withPivot('allocated_amount')
    ->withTimestamps();
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
