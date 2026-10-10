<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularizationItemReceipt extends Model
{
    use HasFactory;

    protected $casts = [
        'allocated_amount'=>'integer'
    ];

     /**
     * Dépense associée au justificatif.
     */
    public function item()
    {
        return $this->belongsTo(
            RegularizationItem::class,
            'regularization_item_id'
        );
    }

    /**
     * Justificatif associé à la dépense.
     */
    public function receipt()
    {
        return $this->belongsTo(
            RegularizationReceipt::class,
            'regularization_receipt_id'
        );
    }
}
