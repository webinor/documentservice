<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileSignature extends Model
{
    use HasFactory;



protected $fillable = [

    'file_id',
    'user_id',

    'page',

    'x',
    'y',

    'width',
    'height',

    'document_signature_id',

    'signed_at'

];




protected $casts = [

    'signed_at'=>'datetime'

];


public function file()
{
    return $this->belongsTo(File::class);
}

    public function documentSignature(): BelongsTo
    {
        return $this->belongsTo(
            DocumentSignature::class,
            'document_signature_id'
        );
    }
}
