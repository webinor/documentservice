<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularizationSheet extends Model implements PayableDocumentInterface
{
    use HasFactory;

    protected $fillable = ["document_id", "reason", "beneficiary", "amount"];

    public function createSettlementRecord(
        string $transactionTypeCode,
        string $transactionCode
    ): void {
        $direction = $this->getSettlementDirection($transactionTypeCode);

        $amount = $this->getSettlementAmount($transactionTypeCode);

        
       

         switch ($transactionTypeCode) {

  

        case 'REGULARIZATION_ADVANCE':

             $this->financialTransactions()->firstOrCreate(
            [
                "transaction_code" => $transactionCode,
            ],
            [
                "transaction_type_code" => $transactionTypeCode,
                "type" => "ADVANCE",
                "adjustment_type" => "NONE",
                "amount" => $amount,
                "direction" => $direction,
                "status" => "PENDING",
                "created_by" => request()->get("user")["id"],
            ]
        );

            break;

        case 'REGULARIZATION_SETTLEMENT':

        

            $this->financialTransactions()->firstOrCreate(
                [
                    'transaction_code' => $transactionCode,
                ],
                [
                    'status' => 'PENDING',
                    'transaction_type_code' => $transactionTypeCode,
                    'type' => 'SETTLEMENT',
                    'adjustment_type' => $direction === 'OUT'
                        ? 'SUPPLEMENT'
                        : 'REFUND',
                    'direction' => $direction,
                    'amount' => abs($amount),
                    "created_by" => request()->get("user")["id"],
                ]
            );


        //      $this->financialTransactions()->firstOrCreate(
        //     [
        //         "transaction_code" => $transactionCode,
        //     ],
        //     [
        //         "transaction_type_code" => $transactionTypeCode,
        //         "type" => "ADVANCE",
        //         "adjustment_type" => "NONE",
        //         "amount" => $amount,
        //         "direction" => $direction,
        //         "status" => "PENDING",
        //         "created_by" => request()->get("user")["id"],
        //     ]
        // );

            break;
    }
    }

    /**
     * Get the document that owns the TaxiPaper
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function financialTransactions()
    {
        return $this->morphMany(FinancialTransaction::class, "transactable");
    }

    public function getSettlementActor(): array
    {
        return [
            "actor_type" => $this->document->actor_type,
            "actor_id" => $this->document->actor_id,
        ];
    }

    public function getSettlementAmount(
        string $transaction_type_code = ""
    ): float {
        return $this->actual_amount - $this->amount;
    }

    public function getSettlementDetails(): array
    {
        return [];
    }

    public function getSettlementReason(string $transaction_type_code): string
    {
        return $this->reason ?? "Paiement avance fiche a regulariser";
    }

    public function getSettlementDirection(
        string $transaction_type_code = ""
    ): string {
        $balance = $this->getSettlementAmount();

        // throw new \Exception(json_encode($balance), 1);

        if ($balance > 0) {
            return "OUT";
        }

        if ($balance < 0) {
            return "IN";
        }

        return "NONE";
    }
}
