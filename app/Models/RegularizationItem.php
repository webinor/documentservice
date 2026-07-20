<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularizationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'regularization_sheet_id',
        'designation',
        'quantity',
        'unit_price',
        'total_amount',
        'receipt',
        'comment',
        'sort_order',
    ];

    protected $casts = [
        // 'quantity' => 'decimal:2',
        // 'unit_price' => 'decimal:2',
        // 'total_amount' => 'decimal:2',
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

public function receipt()
{
    return $this->morphOne(File::class, 'model')
        ->where('type', 'RECEIPT');
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