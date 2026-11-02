<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseSupplierQuotePaymentInstallment extends Model
{
    use HasFactory;


    protected $fillable = [
        'purchase_supplier_quote_id',
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

    public function quote(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseSupplierQuote::class,
            'purchase_supplier_quote_id'
        );
    }
}
