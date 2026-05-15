<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'description',
        'color',
        'category',
        'is_final',
        'triggers_reminder',
        'is_active'
    ];
}
