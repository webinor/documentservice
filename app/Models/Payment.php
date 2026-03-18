<?php

namespace App\Models;

use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'amount',
        'status',
        'payment_method',
        'reference',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }


}