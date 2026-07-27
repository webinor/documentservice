<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentReference extends Model
{
    use HasFactory;

    protected $fillable = [
        "document_id",
        "document_reference_type_id",
        "reference_type_code",
        "reference",
        "attachment",
        "metadata",
        "created_by",
    ];


    protected $casts = [
        "metadata" => "array",
    ];


    /**
     * Get the documentReferenceType that owns the DocumentReference
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function documentReferenceType(): BelongsTo
    {
        return $this->belongsTo(DocumentReferenceType::class,);
    }

      public function file()
    {
        return $this->morphOne(File::class, 'model');
    }

}
