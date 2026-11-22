<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionExpenseReceipt extends Model
{
    protected $table = 'mission_expense_receipt';

    protected $fillable = [
        'mission_expense_id',
        'mission_receipt_id',
        'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'integer',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(
            MissionExpense::class,
            'mission_expense_id'
        );
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(
            MissionReceipt::class,
            'mission_receipt_id'
        );
    }
}