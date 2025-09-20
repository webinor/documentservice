<?php

namespace App\Models\Misc;

use App\Models\Thumbnail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'attachment_type_id',
        'created_by',
        'attachment_number'
    ];

    public function file()
    {
        return $this->morphOne(File::class, 'model');
    }

    public function attachmentType()
    {
        return $this->belongsTo(AttachmentType::class);
    }

    // Relation pratique pour le PDF
public function pdf(): ?File
{
    return $this->files()->where('type', 'pdf')->first();
}

// Relation pratique pour la miniature
public function thumbnail()//: ?File
{
    return $this->hasOne(Thumbnail::class);

  //  return $this->files()->where('type', 'thumbnail')->first();
}
}
