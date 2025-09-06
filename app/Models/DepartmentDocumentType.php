<?php

namespace App\Models;

use App\Models\Misc\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentDocumentType extends Model
{
    use HasFactory;

    /**
     * Get the document_type that owns the DepartmentDocumentType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document_type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class,);
    }
}
