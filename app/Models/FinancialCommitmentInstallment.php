<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialCommitmentInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_commitment_id',
        'sequence',
        'label',
        'trigger_type',
        'days_after_trigger',
        'percentage',
        'expected_amount',
        'due_date',
        'triggered_at',
        'paid_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'days_after_trigger' => 'integer',
        'percentage' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'due_date' => 'date',
        'triggered_at' => 'date',
        'paid_at' => 'date',
    ];

    public function financialCommitment(): BelongsTo
    {
        return $this->belongsTo(
            FinancialCommitment::class,
            'financial_commitment_id'
        );
    }
}
