<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PurchaseReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_order_id',
        'code',
        'reference',
        'physical_receipt_received',
        'physical_receipt_received_at',
        'physical_receipt_received_by_employee_id',
    ];

    protected $casts = [
        'physical_receipt_received' => 'boolean',
        'physical_receipt_received_at' => 'datetime',
    ];

    /**
     * Bon de commande auquel appartient cette réception.
     */
    public function requestOrder(): BelongsTo
    {
        return $this->belongsTo(
            RequestOrder::class,
            'request_order_id'
        );
    }

    /**
     * Fichiers associés à cette réception.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'model');
    }

    /**
     * Fichier principal du justificatif.
     */
    public function file()
    {
        return $this->morphOne(File::class, 'model')
            ->where('type', 'RECEIPT');
    }

    /**
     * Indique si le justificatif possède son fichier principal.
     */
    public function hasReceiptFile(): bool
    {
        return $this->file()->exists();
    }

    /**
     * Échéances de paiement associées à ce justificatif.
     *
     * allocated_amount représente la part de chaque échéance
     * couverte par ce justificatif.
     */
    public function paymentSchedules(): BelongsToMany
    {
        return $this->belongsToMany(
            RequestOrderPaymentSchedule::class,
            'purchase_receipt_payment_schedule',
            'purchase_receipt_id',
            'request_order_payment_schedule_id'
        )
            ->withPivot('allocated_amount')
            ->withTimestamps();
    }
}