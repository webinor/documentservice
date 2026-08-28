<?php

namespace App\Models;

use App\Models\Misc\Document;
use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSignaturePosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'file_id',
        'signature_type',
        'page_number',
        'x',
        'y',
        'width',
        'height',
        'user_id'
    ];

    protected $casts = [
        'document_id' => 'integer',
        'file_id' => 'integer',
        'page_number' => 'integer',
        'x' => 'float',
        'y' => 'float',
        'width' => 'float',
        'height' => 'float',
    ];

    /**
     * Document concerné.
     */
    public function document()
    {
        return $this->belongsTo(
            Document::class,
            'document_id'
        );
    }

    /**
     * Fichier PDF concerné.
     */
    public function file()
    {
        return $this->belongsTo(
            File::class,
            'file_id'
        );
    }

    public function scopeForUser($query, int $userId)
{
    return $query->where('user_id', $userId);
}

public function scopeForDocument($query, int $documentId)
{
    return $query->where('document_id', $documentId);
}

public function scopeReceipt($query)
{
    return $query->where(
        'signature_type',
        'RECEIPT'
    );
}
}