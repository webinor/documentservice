<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentReferenceType extends Model
{
    use HasFactory;

    protected $casts = [
        'has_attachment'=>'boolean'
    ];
}
