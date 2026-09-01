<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    use HasFactory;

     protected $fillable = [
        'document_id',
        'signature_type',
        'signed_by',
        'signature_file',
        'signed_at',
        'metadata',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'metadata' => 'array',
    ]; 

    public function fileSignatures()
{
    return $this->hasMany(
        FileSignature::class,
        'document_signature_id'
    );
}
}
