<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RequestOrderPaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_order_id',
        'payment_term_installment_id',
        'sequence',
        'label',
        'trigger_type',
        'days_after_trigger',
        'percentage',
        'amount',
        'paid_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'days_after_trigger' => 'integer',
        'percentage' => 'decimal:2',
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'due_date' => 'date',
    ];

    /**
     * Bon de commande associé à l'échéance.
     */
    public function requestOrder(): BelongsTo
    {
        return $this->belongsTo(
            RequestOrder::class,
            'request_order_id'
        );
    }

    public function getRemainingAmountAttribute()
{
    return max(
        0,
        (float) $this->amount - (float) $this->paid_amount
    );
}

    /**
     * Ligne du catalogue à l'origine de l'échéance.
     */
    public function paymentTermInstallment(): BelongsTo
    {
        return $this->belongsTo(
            PaymentTermInstallment::class,
            'payment_term_installment_id'
        );
    }

    /**
 * Trigger associé à cette échéance.
 */
public function paymentTrigger(): BelongsTo
{
    return $this->belongsTo(
        PaymentTrigger::class,
        'trigger_type',
        'code'
    );
}

/**
 * Justificatifs associés à cette échéance de paiement.
 */
public function receipts(): BelongsToMany
{
    return $this->belongsToMany(
        PurchaseReceipt::class,
        'purchase_receipt_payment_schedule',
        'request_order_payment_schedule_id',
        'purchase_receipt_id'
    )
        ->withPivot('allocated_amount')
        ->withTimestamps();
}
}