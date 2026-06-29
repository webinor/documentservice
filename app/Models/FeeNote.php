<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeNote extends Model implements PayableDocumentInterface
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'reason',
        'beneficiary',
        'amount',
    ];

 public function createSettlementRecord(
        string $transactionTypeCode,
        string $transactionCode
    ): void {


            $direction = $this->getSettlementDirection($transactionTypeCode);
           
            $amount = $this->getSettlementAmount($transactionTypeCode);


            // ->firstOrCreate(
            //     [
            //         'transaction_code' => $transactionCode,
            //     ],
             $this->financialTransactions()->firstOrCreate(
                 [
                    'transaction_code' => $transactionCode,
                ],
                [
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

          
        
    }

    /**
     * Get the document that owns the TaxiPaper
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class,);
    }

     public function financialTransactions()
    {
        return $this->morphMany(FinancialTransaction::class, 'transactable');
    }

      public function getSettlementActor(): array
    {
        // throw new \Exception(json_encode($this->document), 1);
        
        return ['actor_type' => $this->document->actor_type , 'actor_id' => $this->document->actor_id];
    }

    public function getSettlementAmount(string $transaction_type_code = ""): float
    {
      

            return     $this->amount;
    }


      public function getSettlementDetails(): array
{
    return [];
}

    public function getSettlementReason(string $transaction_type_code): string
    {
        return $this->reason ?? "Reglement Note de frais";
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
