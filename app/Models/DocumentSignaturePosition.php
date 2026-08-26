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
}