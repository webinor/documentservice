<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxiPaper extends Model implements PayableDocumentInterface
{
    use HasFactory;


    protected $fillable = [
        'document_id',
        'reason',
        'rides',
        'beneficiary',
    ];

    protected $casts = [
        'rides' => 'array',
    ];


    public function getPaymentRecipient(): int
    {
        return $this->beneficiary;
    }

    public function getPaymentAmount(): float
    {
        // return collect($this->rides)
        //     ->sum('montant');

            return     $total = collect($this->rides)->reduce(function ($carry, $item) {
            return $carry + (int) ($item['montant'] ?? 0);
        }, 0);
    }

    public function getPaymentReason(): string
    {
        return $this->reason ?? "";
    }
}
