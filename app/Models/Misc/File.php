<?php

namespace App\Models\Misc;

use App\Models\DocumentSignaturePosition;
use App\Models\FileSignature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;


    protected $fillable = [
        'path',
        'size',
        'type',
    'signed_path',
    'signed_at'

    ];

    protected $casts = [
    'page_count' => 'integer',
    'signed_at' => 'datetime',
];




    public function model()
    {
        return $this->morphTo();
    }

//     public function signatures()
// {
//     return $this->hasMany(FileSignature::class);
// }

 /**
     * Signatures réellement effectuées sur ce fichier.
     */
    public function signatures()
    {
        return $this->hasMany(
            FileSignature::class,
            'file_id'
        );
    }

    /**
     * Positions configurées des signatures
     * sur ce fichier.
     */
    public function signaturePositions()
    {
        return $this->hasMany(
            DocumentSignaturePosition::class,
            'file_id'
        );
    }


}