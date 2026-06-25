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


    public function createSettlementRecord(
        string $transactionTypeCode,
        string $transactionCode
    ): void {


            $direction = $this->getSettlementDirection($transactionTypeCode);
           

            $this->regulations()->firstOrCreate(
                [
                    'transaction_code' => $transactionCode,
                ],
                [
                    'amount' => $this->getSettlementAmount($transactionTypeCode),

                       'type' =>'supplement' ,// $direction === 'OUT' ? 'supplement'  : 'refund',

                    'status' => 'PENDING',
                ]
            );

          
        
    }


      public function regulations()
    {
        return $this->hasMany(TaxiRegulation::class);
    }

    public function getSettlementActor(): int
    {
        return $this->beneficiary;
    }

    public function getSettlementAmount(string $transaction_type_code = ""): float
    {
      

            return     $total = collect($this->rides)->reduce(function ($carry, $item) {
            return $carry + (int) ($item['montant'] ?? 0);
        }, 0);
    }


      public function getSettlementDetails(): array
{
    return [];
}

    public function getSettlementReason(string $transaction_type_code): string
    {
        return $this->reason ?? "Reglement Papier Taxi";
    }

          public function getSettlementDirection(string $transaction_type_code = ""): string
{
    $balance = $this->getSettlementAmount();

    if ($balance > 0) {
        return 'OUT';
    }

    if ($balance < 0) {
        return 'IN';
    }

    return 'NONE';
}
}
