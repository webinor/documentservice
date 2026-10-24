<?php

namespace App\Models;

use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'destination_service_id',
        'priority',
        'requested_by',
        'document_id'
    ]; 

    /**
     * Get the document that owns the PurchaseRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class,);
    }

    /**
     * Get all of the purchase_request_items for the PurchaseRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchase_request_items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class,);
    }


    /**
 * Devis fournisseurs associés à la demande d'achat.
 */
public function supplierQuotes(): HasMany
{
    return $this->hasMany(
        PurchaseSupplierQuote::class,
        'purchase_request_id'
    );
}

/**
 * Bons de commande issus de cette demande.
 */
public function requestOrders(): HasMany
{
    return $this->hasMany(
        RequestOrder::class,
        'purchase_request_id'
    );
}

public function financialCommitments(): HasMany
{
    return $this->hasMany(
        FinancialCommitment::class,
        'purchase_request_id'
    );
}
}
