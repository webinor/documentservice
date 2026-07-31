<?php

namespace App\Models;

use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'verification_code',
        'version',
        'snapshot',
        'pdf_hash',
        'is_valid',
    ];


    protected $casts = [
        'snapshot' => 'array',
        'is_valid' => 'boolean',
    ];


    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}