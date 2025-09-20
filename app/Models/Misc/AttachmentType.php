<?php

namespace App\Models\Misc;

use App\Models\AttachmentTypeCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttachmentType extends Model
{
    use HasFactory;

    /**
     * Get the attachmentTypeCategory that owns the AttachmentType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attachmentTypeCategory(): BelongsTo
    {
        return $this->belongsTo(AttachmentTypeCategory::class);
        
    }
}
