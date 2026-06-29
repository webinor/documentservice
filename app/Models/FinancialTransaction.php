<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $table = 'financial_transactions';

    protected $fillable = [
        'transaction_code',
        'transaction_type_code',

        // lien métier
        'transactable_id',
        'transactable_type',

        // nature financière
        'type', // ADVANCE | SETTLEMENT | REFUND | SUPPLEMENT
        'direction', // IN | OUT

        // montant
        'amount',

        // statut paiement
        'status', // PENDING | PROCESSING | PAID | FAILED | CANCELLED

        // paiement externe
        'payment_method',
        'reference',

        // audit
        'created_by',
        'processed_at',
        'paid_at',
        'comment',

        // extensible
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | POLYMORPHISM
    |--------------------------------------------------------------------------
    */
    public function transactable()
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED');
    }

    public function scopeAdvances($query)
    {
        return $query->where('type', 'ADVANCE');
    }

    public function scopeSettlements($query)
    {
        return $query->where('type', 'SETTLEMENT');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */
    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isAdvance(): bool
    {
        return $this->type === 'ADVANCE';
    }

    public function isSettlement(): bool
    {
        return $this->type === 'SETTLEMENT';
    }

    public function isInflow(): bool
    {
        return $this->direction === 'IN';
    }

    public function isOutflow(): bool
    {
        return $this->direction === 'OUT';
    }
}