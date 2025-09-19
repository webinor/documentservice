<?php

namespace App\Models;

use App\Models\Finance\InvoiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerCode extends Model
{
    use HasFactory;

     protected $fillable = [
        'code',
        'label',
        'ledger_code_type_id',
    ];

    /**
     * Relation : un code appartient à un type.
     */
    public function ledgerCodeType()
    {
        return $this->belongsTo(LedgerCodeType::class, 'ledger_code_type_id');
    }

    /**
     * Get the invoice_provider that owns the LedgerCode
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoice_provider(): BelongsTo
    {
        return $this->belongsTo(InvoiceProvider::class);
    }
}
