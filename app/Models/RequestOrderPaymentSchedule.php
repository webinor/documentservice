<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'days_after_trigger' => 'integer',
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
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
}