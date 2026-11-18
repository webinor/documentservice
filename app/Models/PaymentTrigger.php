<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function paymentModes()
{
    return $this->hasMany(
        PaymentTriggerMode::class,
        'payment_trigger_id'
    );
}

/**
 * Échéances associées à ce trigger.
 */
public function paymentSchedules(): HasMany
{
    return $this->hasMany(
        RequestOrderPaymentSchedule::class,
        'trigger_type',
        'code'
    );
}

    /**
     * Retourne uniquement les déclencheurs actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
