<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerCodeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relation : un type possède plusieurs codes comptables.
     */
    public function ledgerCodes()
    {
        return $this->hasMany(LedgerCode::class, 'ledger_code_type_id');
    }
}
