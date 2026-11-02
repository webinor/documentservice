<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'description',
        'sequence',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sequence' => 'integer',
    ];

    /**
     * Retourne uniquement les déclencheurs actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
