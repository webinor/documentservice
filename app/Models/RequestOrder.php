<?php

namespace App\Models;

use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'purchase_request_id',
        'order_number',
        'supplier_id',
        'supplier_name',
        'supplier_reference',
        'order_date',
        'expected_delivery_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'status',
        'notes',
        'created_by',
        'updated_by',

        'financial_commitment_id',

        'payment_term_id',
'payment_term_code_snapshot',
'payment_term_name_snapshot',
'payment_term_description_snapshot',
'payment_mode_snapshot',
'payment_due_days_snapshot',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Demande d'achat d'origine.
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseRequest::class,
            'purchase_request_id'
        );
    }

    /**
     * Document GED associé au bon de commande.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class,
            'document_id'
        );
    }

    /**
     * Lignes du bon de commande.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            RequestOrderItem::class,
            'request_order_id'
        )->orderBy('position');
    }

    /**
 * Condition de paiement du catalogue.
 */
public function paymentTerm(): BelongsTo
{
    return $this->belongsTo(
        PaymentTerm::class,
        'payment_term_id'
    );
}

/**
 * Échéancier enregistré pour ce bon de commande.
 */
public function paymentSchedules(): HasMany
{
    return $this->hasMany(
        RequestOrderPaymentSchedule::class,
        'request_order_id'
    )->orderBy('sequence');
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestOrder extends Model
{
    protected $fillable = [
        'document_id',
        'purchase_request_id',
        'financial_commitment_id',
    ];

    public function financialCommitment(): BelongsTo
    {
        return $this->belongsTo(
            FinancialCommitment::class,
            'financial_commitment_id'
        );
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseRequest::class,
            'purchase_request_id'
        );
    }
}
}