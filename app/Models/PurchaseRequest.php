<?php

namespace App\Models;

use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'destination_service_id',
        'priority',
        'requested_by',
        'document_id'
    ]; 

    /**
     * Get the document that owns the PurchaseRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class,);
    }

    /**
     * Get all of the purchase_request_items for the PurchaseRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchase_request_items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class,);
    }


}
