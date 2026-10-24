<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'payment_mode',
        'due_days',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_days' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Échéances types définies pour cette condition.
     */
    public function installments(): HasMany
    {
        return $this->hasMany(
            PaymentTermInstallment::class,
            'payment_term_id'
        )->orderBy('sequence');
    }

    /**
     * Bons de commande utilisant cette condition.
     */
    public function requestOrders(): HasMany
    {
        return $this->hasMany(
            RequestOrder::class,
            'payment_term_id'
        );
    }

    /**
     * Conditions proposées lors de la création d'un bon.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}