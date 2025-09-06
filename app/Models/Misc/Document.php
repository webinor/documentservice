<?php

namespace App\Models\Misc;

use App\Models\Finance\InvoiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'document_type_id',
        'department_id',
        'workflow_id',
        'created_by',
    ];


    /**
     * Get the document_type that owns the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document_type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class,);
    }

    /**
     * Get the invoice_provider associated with the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function invoice_provider(): HasOne
    {
        return $this->hasOne(InvoiceProvider::class, );
    }

    public function getCreatedAtAttribute($value)
    {
        if (!$value ) {
            return null; // ou return '';
        }
        return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
    }

    /**
     * Get the attachment associated with the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function attachment(): HasOne
    {
        return $this->hasOne(Attachment::class,);
    }
}
