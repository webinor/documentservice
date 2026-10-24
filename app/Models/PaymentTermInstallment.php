<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTermInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_term_id',
        'sequence',
        'label',
        'trigger_type',
        'days_after_trigger',
        'percentage',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'days_after_trigger' => 'integer',
        'percentage' => 'decimal:2',
    ];

    /**
     * Condition de paiement à laquelle appartient cette échéance.
     */
    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(
            PaymentTerm::class,
            'payment_term_id'
        );
    }
}