<?php

namespace App\Models\Finance;

use App\Models\LedgerCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvoiceProvider extends Model
{
    use HasFactory;


    public function getDepositDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}

/**
 * Get the ledger_code associated with the InvoiceProvider
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function ledger_code(): HasOne
{
    return $this->hasOne(LedgerCode::class);
}


}
