<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_order_id',
        'purchase_request_item_id',
        'designation',
        'description',
        'specification',
        'unit',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'position',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Bon de commande auquel appartient la ligne.
     */
    public function requestOrder(): BelongsTo
    {
        return $this->belongsTo(
            RequestOrder::class,
            'request_order_id'
        );
    }

    /**
     * Article correspondant dans la demande initiale.
     */
    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseRequestItem::class,
            'purchase_request_item_id'
        );
    }
}