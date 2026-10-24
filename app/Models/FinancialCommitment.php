<?php

namespace App\Models;

use App\Models\Finance\InvoiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialCommitment extends Model
{
    protected $fillable = [
        'document_id',
        'purchase_request_id',
        'commitment_number',
        'commitment_type',
        'supplier_id',
        'supplier_name',
        'title',
        'initial_amount',
        'approved_amount',
        'currency',
        'start_date',
        'end_date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'purchase_request_id' => 'integer',
        'supplier_id' => 'integer',
        'initial_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseRequest::class,
            'purchase_request_id'
        );
    }

    public function requestOrder(): HasOne
    {
        return $this->hasOne(
            RequestOrder::class,
            'financial_commitment_id'
        );
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(
            InvoiceProvider::class,
            'financial_commitment_id'
        );
    }
}