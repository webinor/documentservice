<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularizationItem extends Model
{
    use HasFactory;

    protected $fillable = [
       
        'total_amount',
        'sort_order',
            'regularization_sheet_id',
    'designation',

    'planned_quantity',
    'actual_quantity',

    'unit_price',

    'planned_amount',
    'actual_amount',

    'old_receipt',
    'comment',
    'sort_order',
    ];
    

   protected $casts = [
    'planned_quantity' => 'integer',
    'actual_quantity'  => 'integer',

    'unit_price'       => 'integer',

    'planned_amount'   => 'integer',
    'actual_amount'    => 'integer',

    'sort_order'       => 'integer',
];

    /**
     * Fiche de régularisation parente.
     */
    public function regularizationSheet()
    {
        return $this->belongsTo(RegularizationSheet::class);
    }

    public function files()
{
    return $this->morphMany(File::class, 'model');
}

// public function receipt()
// {
//     return $this->morphOne(File::class, 'model')
//         ->where('type', 'RECEIPT');
// }

public function receipts()
{
    return $this->belongsToMany(
        RegularizationReceipt::class,
        'regularization_item_receipt',
        'regularization_item_id',
        'regularization_receipt_id'
    )->withPivot('allocated_amount');
}

    protected static function booted()
{
    static::saving(function ($item) {
        $item->total_amount =
            ($item->quantity ?? 0) *
            ($item->unit_price ?? 0);
    });
}
}