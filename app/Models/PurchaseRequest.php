<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseRequest extends Model implements PayableDocumentInterface
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'destination_service_id',
        'priority',
        'requested_by',
        'document_id',
        'client_id',
        'category',
        'type'
    ]; 

     public function financialTransactions()
    {
        return $this->morphMany(FinancialTransaction::class, "transactable");
    }


    public function getSettlementActor(): array{

    $purchaseOrder = $this->request_order;
    $actor = $purchaseOrder->payment_agent;

    //throw new Exception(json_encode($purchaseOrder->payment_agent_employee_id), 1);

     return [

            "actor_type" => "EMPLOYEE",
            "actor_id" => $purchaseOrder->payment_agent_employee_id

        ];

    }
/**
 * Récupère le montant de l'échéance de paiement initial.
 *
 * @param string $transaction_type_code
 * @return float
 */
public function getSettlementAmount(
    string $transaction_type_code
): float {


 $purchaseOrder = $this->request_order;

    if (!$purchaseOrder) {

    return 0;
    
        throw new Exception(
            "Aucun bon de commande associé à cette demande d'achat."
        );
    }

    if ($transaction_type_code === 'PURCHASE_ADVANCE' || $transaction_type_code == "UNKNOWN" ) {
       
    

    $schedule = $purchaseOrder->paymentSchedules()
        ->whereHas('paymentTrigger', function ($query) {
            $query->where('is_initial_payment', true);
        })
        ->orderBy('sequence')
        ->first();

    if (!$schedule) {

    return 0;
    
        throw new Exception(
            "Aucune échéance de paiement initial trouvée pour ce bon de commande."
        );
    }

    return (float) $schedule->amount;

    }
    else if($transaction_type_code === 'PURCHASE_SETTLEMENT'){

    return 0;

    }

    else {

    return $purchaseOrder->total_amount;

    }

   

}

    public function getSettlementReason(string $transaction_type_code): string {
        return $this->description;
    }

     public function getSettlementDirection(
        string $transaction_type_code = ""
    ): string {



         switch ($transaction_type_code) {

  

        case 'PURCHASE_ADVANCE':

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

    public function getSettlementDetails(): array {
        return [];
    }

    public function createSettlementRecord(
    string $transactionTypeCode,
    string $transactionCode
): void {

    $direction = $this->getSettlementDirection(
        $transactionTypeCode
    );

    $amount = $this->getSettlementAmount(
        $transactionTypeCode
    );

    switch ($transactionTypeCode) {

        case 'PURCHASE_ADVANCE':

            $this->financialTransactions()->firstOrCreate(
                [
                    'transaction_code' => $transactionCode,
                ],
                [
                    'transaction_type_code' => $transactionTypeCode,
                    'type' => 'ADVANCE',
                    'adjustment_type' => 'NONE',
                    'amount' => abs($amount),
                    'direction' => 'OUT',
                    'status' => 'PENDING',
                    'created_by' => request()->get('user')['id'],
                ]
            );

            break;

        case 'PURCHASE_SETTLEMENT':

            $this->financialTransactions()->firstOrCreate(
                [
                    'transaction_code' => $transactionCode,
                ],
                [
                    'transaction_type_code' => $transactionTypeCode,
                    'type' => 'SETTLEMENT',
                    'adjustment_type' => $direction === 'OUT'
                        ? 'SUPPLEMENT'
                        : ($direction === 'IN' ? 'REFUND' : 'NONE'),
                    'amount' => abs($amount),
                    'direction' => $direction,
                    'status' => 'PENDING',
                    'created_by' => request()->get('user')['id'],
                ]
            );

            break;
    }
}

    /**
     * Get the document that owns the PurchaseRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class,);
    }

    /**
     * Get all of the purchase_request_items for the PurchaseRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchase_request_items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class,);
    }

    /**
     * Get the request_order associated with the PurchaseRequest 
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function request_order(): HasOne
    {
        return $this->hasOne(RequestOrder::class);
    }

    /**
 * Devis fournisseurs associés à la demande d'achat.
 */
public function supplierQuotes(): HasMany
{
    return $this->hasMany(
        PurchaseSupplierQuote::class,
        'purchase_request_id'
    );
}

/**
 * Bons de commande issus de cette demande.
 */
public function requestOrders(): HasMany
{
    return $this->hasMany(
        RequestOrder::class,
        'purchase_request_id'
    );
}

public function financialCommitments(): HasMany
{
    return $this->hasMany(
        FinancialCommitment::class,
        'purchase_request_id'
    );
}
}
