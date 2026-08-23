<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularizationReceipt extends Model
{
    use HasFactory;

protected $fillable = [
        'regularization_sheet_id',
        'reference',
        'receipt_date',
        'description',
        'code'
    ];

    public function sheet()
    {
        return $this->belongsTo(
            RegularizationSheet::class,
            'regularization_sheet_id'
        );
    }

    

    public function items()
{
    return $this->belongsToMany(
        RegularizationItem::class,
        'regularization_item_receipt',
        'regularization_receipt_id',
        'regularization_item_id'
    )->withPivot('allocated_amount');
}

    public function files()
    {
        return $this->morphMany(File::class, 'model');
    }

    public function file()
    {
        return $this->morphOne(File::class, 'model')
            ->where('type', 'RECEIPT');
    }
}
