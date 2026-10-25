<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'client_id'
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_selected' => 'boolean',
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
}