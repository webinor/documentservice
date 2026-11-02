<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseSupplierQuote extends Model
{
    use HasFactory;

    protected $table = 'purchase_supplier_quotes';

    protected $fillable = [
        'purchase_request_id',
        'supplier_id',
        'reference',
        'amount',
        'comment',
        'attachment_id',
        'is_selected',
        'status',
        'created_by',
        'updated_by',
        'selection_reason',
        'quote_date',
        'delivery_days',
        'client_id',
        'payment_term_id',
        'has_custom_payment_terms',
        
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_selected' => 'boolean',
        'has_custom_payment_terms' => 'boolean',
    ];

  

    /**
     * Demande d'achat associée au devis.
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseRequest::class,
            'purchase_request_id'
        );
    }

   public function files(): MorphMany
{
    return $this->morphMany(File::class, 'model');
}

/**
 * Condition de paiement issue du catalogue.
 */
public function paymentTerm(): BelongsTo
{
    return $this->belongsTo(
        PaymentTerm::class,
        'payment_term_id'
    );
}

/**
 * Get the request_order associated with the PurchaseSupplierQuote
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function request_order(): HasOne
{
    return $this->hasOne(RequestOrder::class,'supplier_quote_id');
}

/**
 * Échéances réellement négociées pour ce devis.
 */
public function paymentInstallments(): HasMany
{
    return $this->hasMany(
        PurchaseSupplierQuotePaymentInstallment::class,
        'purchase_supplier_quote_id'
    )->orderBy('sequence');
}
}