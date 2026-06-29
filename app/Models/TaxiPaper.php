<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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


    /**
     * Get the document that owns the TaxiPaper
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class,);
    }
    public function createSettlementRecord(
        string $transactionTypeCode,
        string $transactionCode
    ): void {


            $direction = $this->getSettlementDirection($transactionTypeCode);
            $amount = $this->getSettlementAmount($transactionTypeCode);

            $this->financialTransactions()->create([
    'transaction_code' => $transactionCode,
    'transaction_type_code' => $transactionTypeCode,
    'type' => 'ONE_SHOT',
    'adjustment_type' => 'NONE',
    'amount' => $amount,
    'direction' => $direction,
    'status' => 'PENDING',
    // 'paid_at' => now(),
    // 'processed_at' => now(),
    'created_by' => request()->get('user')['id']
]);
           

            // $this->regulations()->firstOrCreate(
            //     [
            //         'transaction_code' => $transactionCode,
            //     ],
            //     [
            //         'amount' => $this->getSettlementAmount($transactionTypeCode),

            //            'type' =>'supplement' ,// $direction === 'OUT' ? 'supplement'  : 'refund',

            //         'status' => 'PENDING',
            //     ]
            // );

          
        
    }


      public function financialTransactions()
    {
        return $this->morphMany(FinancialTransaction::class, 'transactable');
    }
    public function getSettlementActor(): array
    {
        return ['actor_type' => $this->document->actor_type , 'actor_id' => $this->document->actor_id];
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
