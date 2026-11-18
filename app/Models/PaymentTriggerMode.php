<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTriggerMode extends Model
{
    use HasFactory;



    protected $fillable = [
        'payment_trigger_id',
        'payment_mode',
    ];

    public function paymentTrigger()
    {
        return $this->belongsTo(
            PaymentTrigger::class,
            'payment_trigger_id'
        );
    }
}
