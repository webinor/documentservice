<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use App\Services\Regularization\RegularizationFinancialSummaryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularizationSheet extends Model implements PayableDocumentInterface
{
    use HasFactory;

    protected $fillable = ["document_id", "reason", "beneficiary", "amount" , 'case_number',  'assistance_mode',"regularization_type"];


    public function items()
{
    return $this->hasMany(
        RegularizationItem::class
    )->orderBy('sort_order');
}

public function receipts()
{
    return $this->hasMany(
        RegularizationReceipt::class
    );
}

    public function createSettlementRecord(
        string $transactionTypeCode,
        string $transactionCode
    ): void {
        $direction = $this->getSettlementDirection($transactionTypeCode);

        $amount = $this->getSettlementAmount($transactionTypeCode);

        
       
        // throw new \Exception(json_encode($direction), 1);
        

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
                "amount" => abs($amount),
                "direction" => "OUT",// $direction,
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

//"REGULARIZATION_ADVANCE","REGULARIZATION_SETTLEMENT"

// throw new \Exception($transaction_type_code, 1);


     if ($transaction_type_code == "REGULARIZATION_ADVANCE") {
          
   
     return (float) $this->items->sum(function ($item) {
    return (float) $item->planned_amount
        * (float) ($item->planned_quantity ?? 1);
});
        }

    elseif($transaction_type_code == "REGULARIZATION_SETTLEMENT"){



         $summary = app(RegularizationFinancialSummaryService::class)->build($this->document);

        $totalRealExpenses = $summary["total_reel"];


        $totalAdvances = $summary["total_advance"];

    // throw new \Exception(($totalRealExpenses + $totalAllowances - $totalAdvances), 1);
        

        /**
         * Résultat :
         *
         * > 0  => la caisse doit payer le missionnaire
         * < 0  => le missionnaire doit rembourser
         * = 0  => équilibré
         */
        return ((float) ($totalRealExpenses  - $totalAdvances));

    }
    else{


                    return (float) $this->items->sum(function ($item) {
    return (float) $item->planned_amount
        * (float) ($item->planned_quantity ?? 1);
});

    } 
    // return app(RegularizationFinancialSummaryService::class)->build($document);

     
          

          

  
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



         switch ($transaction_type_code) {

  

        case 'REGULARIZATION_ADVANCE':

            return "OUT";

                    
            break;
    }



        $balance = $this->getSettlementAmount($transaction_type_code);

       
        

        if ($balance > 0) {
            return "OUT";
        }

        if ($balance < 0) {
            return "IN";
        }

        return "NONE";
    }
}
