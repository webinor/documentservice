<?php

namespace App\Services\Financial;

use App\Models\FinancialTransaction;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseRequest;
use App\Models\PurchaseSupplierQuote;
use App\Models\RequestOrderPaymentSchedule;
use App\Services\Supplier\PurchaseSupplierQuoteEnrichmentService;
use App\Services\Supplier\SupplierServiceClient;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseFinancialSummaryService
{
    /**
     * Nombre minimum de devis attendus.
     *
     * @var int
     */
    private const MINIMUM_QUOTES_REQUIRED = 1;

    /**
     * Nombre minimum de devis sélectionnés.
     *
     * @var int
     */
    private const MINIMUM_SELECTED_QUOTES_REQUIRED = 1;


    /**
 * @var PurchaseSupplierQuoteEnrichmentService
 */
private $quoteEnrichmentService;

    /**
     * @var SupplierServiceClient
     */
    private $supplierServiceClient;

    public function __construct(
        SupplierServiceClient $supplierServiceClient,
        PurchaseSupplierQuoteEnrichmentService $quoteEnrichmentService

    ) {
        $this->supplierServiceClient = $supplierServiceClient;
        $this->quoteEnrichmentService = $quoteEnrichmentService;

    }


    /**
     * Construit le résumé financier d'une demande d'achat GED.
     *
     * @param int $documentId
     * @return array
     */
    public function build(int $documentId): array
    {
        /*
         * Récupération de la demande d'achat.
         *
         * DB::table() retourne un stdClass.
         * Il ne faut donc pas utiliser get_class() pour identifier
         * le type polymorphique des transactions.
         */
        $purchaseRequest = DB::table('purchase_requests')
            ->where('document_id', $documentId)
            ->first();

        if (!$purchaseRequest) {
            return $this->emptySummary($documentId);
        }

        /*
         * Articles de la demande d'achat.
         */
        $items = DB::table('purchase_request_items')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->get();

        $requestedQuantityTotal = 0;
        $approvedQuantityTotal = 0;
        $effectiveQuantityTotal = 0;

        $requestedAmountTotal = 0;
        $approvedAmountTotal = 0;
        $effectiveAmountTotal = 0;

        $itemsWithoutQuantity = 0;
        $itemsWithoutAmount = 0;

        foreach ($items as $item) {
            $requestedQuantity = $item->requested_quantity;
            $approvedQuantity = $item->approved_quantity;

            $requestedUnitPrice = $item->requested_amount;
            $approvedUnitPrice = $item->approved_amount;

            /*
             * Totaux des quantités.
             */
            $requestedQuantityTotal += (int) ($requestedQuantity ?? 0);
            $approvedQuantityTotal += (int) ($approvedQuantity ?? 0);

            $effectiveQuantity = $approvedQuantity !== null
                ? (int) $approvedQuantity
                : (int) ($requestedQuantity ?? 0);

            $effectiveQuantityTotal += $effectiveQuantity;

            if (
                $requestedQuantity === null
                && $approvedQuantity === null
            ) {
                $itemsWithoutQuantity++;
            }

            /*
             * Montant total demandé.
             */
            if (
                $requestedQuantity !== null
                && $requestedUnitPrice !== null
            ) {
                $requestedAmountTotal +=
                    (float) $requestedQuantity
                    * (float) $requestedUnitPrice;
            }

            /*
             * Montant total approuvé.
             */
            if (
                $approvedQuantity !== null
                && $approvedUnitPrice !== null
            ) {
                $approvedAmountTotal +=
                    (float) $approvedQuantity
                    * (float) $approvedUnitPrice;
            }

            /*
             * Les valeurs approuvées sont prioritaires.
             */
            $effectiveUnitPrice = $approvedUnitPrice !== null
                ? (float) $approvedUnitPrice
                : (
                    $requestedUnitPrice !== null
                        ? (float) $requestedUnitPrice
                        : null
                );

            if (
                $effectiveQuantity > 0
                && $effectiveUnitPrice !== null
            ) {
                $effectiveAmountTotal +=
                    $effectiveQuantity * $effectiveUnitPrice;
            } else {
                $itemsWithoutAmount++;
            }
        }

        /*
         * Récupération de tous les devis de la demande.
         */
        $quotes = DB::table('purchase_supplier_quotes')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->orderBy('id')
            ->get();

        /*
         * Récupération groupée des fichiers associés aux devis.
         */
        $quoteIds = $quotes->pluck('id')->all();

        $quoteFiles = collect();

        if (!empty($quoteIds)) {
            $quoteFiles = DB::table('files')
                ->where(
                    'model_type',
                    PurchaseSupplierQuote::class
                )
                ->whereIn('model_id', $quoteIds)
                ->whereNotNull('path')
                ->where('path', '<>', '')
                ->get()
                ->groupBy('model_id');
        }

        /*
         * Identification du devis sélectionné.
         */
        $selectedQuote = $quotes
            ->filter(function ($quote) {
                return (bool) $quote->is_selected
                    || $quote->status === 'SELECTED';
            })
            ->sortByDesc('id')
            ->first();

        /*
         * Informations du fournisseur sélectionné.
         */
        // $selectedSupplier = null;

        // if (
        //     $selectedQuote
        //     && $selectedQuote->supplier_id !== null
        // ) {
        //     $selectedSupplier = $this->supplierServiceClient->getSupplier(
        //         (int) $selectedQuote->supplier_id
        //     );
        // }

        /*
|--------------------------------------------------------------------------
| Enrichissement du devis sélectionné
|--------------------------------------------------------------------------
|
| On récupère le modèle Eloquent avec les mêmes relations
| que celles utilisées dans l'endpoint de récupération des devis.
|
| Le service mutualisé s'occupe ensuite :
| - du fournisseur ;
| - des fichiers ;
| - des URL des fichiers.
|
*/

$selectedSupplier = null;

if ($selectedQuote && $selectedQuote->supplier_id !== null) {
    $selectedQuoteModel = PurchaseSupplierQuote::query()
        ->with([
            'request_order.items',
            'files',
            'paymentTerm',
            'paymentInstallments',
        ])
        ->find($selectedQuote->id);

    $selectedQuote = $this->quoteEnrichmentService->enrichOne(
        $selectedQuoteModel
    );

      $selectedSupplier = $this->supplierServiceClient->getSupplier(
        (int) $selectedQuote->supplier_id
    );
}

        /*
         * Bon de commande associé au devis sélectionné.
         */
        $requestOrderSummary = null;

        if ($selectedQuote) {
            $requestOrder = DB::table('request_orders')
                ->where('supplier_quote_id', $selectedQuote->id)
                ->orderByDesc('id')
                ->first();

            if ($requestOrder) {
                /*
                 * Articles du bon de commande.
                 */
                $requestOrderItems = DB::table('request_order_items')
                    ->where('request_order_id', $requestOrder->id)
                    ->orderBy('id')
                    ->get();

                /*
                 * Calcul du montant total des articles.
                 */
                $requestOrderItemsTotal = 0;

                foreach ($requestOrderItems as $item) {
                    $quantity = (float) ($item->quantity ?? 0);
                    $unitPrice = (float) ($item->unit_price ?? 0);

                    $lineTotal = $quantity * $unitPrice;

                    $requestOrderItemsTotal += $lineTotal;

                    $item->line_total = round($lineTotal, 2);
                }

                /*
                 * Récupération des échéances de paiement.
                 */
                $schedules = RequestOrderPaymentSchedule::
                    with('paymentTrigger')
                    ->where('request_order_id', $requestOrder->id)
                    ->orderBy('id')
                    ->get();

                /*
                 * Montant total payé sur toutes les échéances.
                 */
                $requestOrderPaidAmount = (float) $schedules->sum(
                    function ($schedule) {
                        return (float) ($schedule->paid_amount ?? 0);
                    }
                );

                /*
                 * Montant total du bon de commande.
                 */
                $requestOrderTotalAmount = (float) (
                    $requestOrder->total_amount ?? 0
                );

                /*
                 * Reste à payer sur le bon de commande.
                 */
                $requestOrderRemainingAmount = round(
                    $requestOrderTotalAmount - $requestOrderPaidAmount,
                    2
                );

                /*
                 * Identification de l'échéance correspondant
                 * à l'avance initiale.
                 */
                $initialPaymentSchedule = $schedules->first(
                    function ($schedule) {
                        return $this->isInitialPaymentSchedule($schedule);
                    }
                );

                // throw new Exception(json_encode($initialPaymentSchedule), 1);
                

                /*
                 * Montant initialement prévu.
                 */
                $initialPaymentAmount = $initialPaymentSchedule
                    ? (float) ($initialPaymentSchedule->amount ?? 0)
                    : 0;

                /*
                 * Montant effectivement payé sur l'avance initiale.
                 *
                 * Attention :
                 * cette valeur ne sera pas mise à jour lors
                 * des régularisations ultérieures.
                 */
                $initialPaymentPaidAmount = $initialPaymentSchedule
                    ? (float) ($initialPaymentSchedule->paid_amount ?? 0)
                    : 0;

                /*
                 * Le montant à régulariser est calculé à partir
                 * de l'échéance initiale.
                 *
                 * Positif : supplément à payer.
                 * Négatif : remboursement à effectuer.
                 * Zéro    : aucune différence initiale.
                 */
                $amountToRegularize = round(
                    $initialPaymentAmount - $initialPaymentPaidAmount,
                    2
                );

                /*
                 * Le paiement initial est considéré comme effectué
                 * dès qu'un montant a effectivement été payé.
                 */
                $initialPaymentMade = $initialPaymentPaidAmount > 0;

                /*
                 * Paiement initial intégralement effectué.
                 */
                $initialPaymentCompleted =
                    $initialPaymentSchedule !== null
                    && $initialPaymentPaidAmount >= $initialPaymentAmount;

                /*
                 * Récupération des transactions financières
                 * associées à la demande d'achat.
                 *
                 * Les transactions sont recherchées avec la vraie
                 * classe du modèle polymorphique PurchaseRequest.
                 */
                $transactions = FinancialTransaction::query()
                    ->where(
                        'transactable_type',
                        PurchaseRequest::class
                    )
                    ->where(
                        'transactable_id',
                        $purchaseRequest->id
                    )
                    ->where('status', 'PAID')
                    ->get();

                /*
                 * Paiements initiaux.
                 */
                $advanceTransactions = $transactions->where(
                    'transaction_type_code',
                    'PURCHASE_ADVANCE'
                );

                $totalAdvance = (float) $advanceTransactions->sum(
                    'amount'
                );

                /*
                 * Transactions de régularisation.
                 *
                 * Seules les transactions PURCHASE_SETTLEMENT
                 * sont prises en compte pour déterminer
                 * le montant réellement régularisé.
                 */
                $settlementTransactions = $transactions->where(
                    'transaction_type_code',
                    'PURCHASE_SETTLEMENT'
                );

                /*
                 * Remboursements effectués.
                 */
                $refundTransactions = $settlementTransactions->where(
                    'adjustment_type',
                    'REFUND'
                );

                $totalRefund = (float) $refundTransactions->sum(
                    'amount'
                );

                /*
                 * Suppléments payés.
                 */
                $supplementTransactions = $settlementTransactions->where(
                    'adjustment_type',
                    'SUPPLEMENT'
                );

                $totalSupplement = (float) $supplementTransactions->sum(
                    'amount'
                );

                /*
                 * Montant brut des transactions de régularisation.
                 */
                $totalSettlement = (float) $settlementTransactions->sum(
                    'amount'
                );

                /*
                 * Montant net réellement régularisé.
                 *
                 * Un supplément augmente le montant régularisé.
                 * Un remboursement le diminue.
                 */
                $settledAmount = round(
                    $totalSupplement - $totalRefund,
                    2
                );

                /*
                 * Montant restant à régulariser.
                 *
                 * Il s'agit de la différence entre :
                 * - le montant initial à régulariser ;
                 * - le montant net réellement régularisé.
                 */
                $remainingToRegularize = round(
                    $amountToRegularize - $settledAmount,
                    2
                );

                /*
                 * Présence d'au moins une transaction de régularisation.
                 */
                $hasFinancialMovement = $settlementTransactions->isNotEmpty();

                /*
                 * La régularisation est terminée uniquement si :
                 * - le paiement initial a été effectué ;
                 * - le montant net régularisé correspond au montant attendu.
                 *
                 * On utilise une tolérance de 0,01 pour éviter
                 * les problèmes de comparaison des nombres décimaux.
                 */
                $financialMovementCompleted =
                    $initialPaymentMade
                    && abs($remainingToRegularize) < 0.01;

                /*
                 * Une régularisation est nécessaire lorsqu'un montant
                 * reste à régulariser et que le paiement initial a été fait.
                 */
                $regularizationRequired =
                    $initialPaymentMade
                    && abs($remainingToRegularize) >= 0.01;

                /*
                 * La régularisation est terminée lorsqu'il ne reste
                 * aucun montant à régulariser.
                 */
                $regularizationCompleted = $financialMovementCompleted;

                /*
                 * Statut financier de la régularisation.
                 */
                $settlementStatus = $this->resolvePurchaseSettlementStatus(
                    $initialPaymentSchedule !== null,
                    $initialPaymentMade,
                    $remainingToRegularize,
                    $regularizationCompleted
                );

                /*
                 * Structure finale du bon de commande.
                 */
                $requestOrderSummary = [
                    'id' => (int) $requestOrder->id,

                    /*
                     * Informations générales.
                     */
                    'request_order' => (array) $requestOrder,

                    /*
                     * Articles.
                     */
                    'items' => $requestOrderItems,

                    'items_count' => $requestOrderItems->count(),

                    'items_total_amount' => round(
                        $requestOrderItemsTotal,
                        2
                    ),

                    /*
                     * Échéances de paiement.
                     */
                    'schedules' => $schedules,

                    'schedules_count' => $schedules->count(),

                    /*
                     * Synthèse financière globale du bon de commande.
                     */
                    'total_amount' => round(
                        $requestOrderTotalAmount,
                        2
                    ),

                    'paid_amount' => round(
                        $requestOrderPaidAmount,
                        2
                    ),

                    'remaining_amount' => $requestOrderRemainingAmount,

                    /*
                     * Informations relatives à l'avance initiale
                     * et à sa régularisation.
                     */
                    'regularization' => [
                        /*
                         * Échéance initiale.
                         */
                        'initial_payment_found' =>
                            $initialPaymentSchedule !== null,

                        'initial_payment_schedule_id' =>
                            $initialPaymentSchedule
                                ? (int) $initialPaymentSchedule->id
                                : null,

                        'initial_payment_amount' => round(
                            $initialPaymentAmount,
                            2
                        ),

                        'initial_payment_paid_amount' => round(
                            $initialPaymentPaidAmount,
                            2
                        ),

                        'initial_payment_made' =>
                            $initialPaymentMade,

                        'initial_payment_completed' =>
                            $initialPaymentCompleted,

                        /*
                         * Montant attendu pour la régularisation.
                         */
                        'amount_to_regularize' =>
                            $amountToRegularize,

                        /*
                         * Transactions de régularisation.
                         */
                        'total_settlement' => round(
                            $totalSettlement,
                            2
                        ),

                        'total_refund' => round(
                            $totalRefund,
                            2
                        ),

                        'total_supplement' => round(
                            $totalSupplement,
                            2
                        ),

                        /*
                         * Montant net effectivement régularisé.
                         */
                        'settled_amount' => $settledAmount,

                        /*
                         * Montant restant à régulariser.
                         */
                        'remaining_to_regularize' =>
                            $remainingToRegularize,

                        /*
                         * Indicateurs de suivi.
                         */
                        'has_financial_movement' =>
                            $hasFinancialMovement,

                        'financial_movement_completed' =>
                            $financialMovementCompleted,

                        'regularization_required' =>
                            $regularizationRequired,

                        'regularization_completed' =>
                            $regularizationCompleted,

                        'settlement_status' =>
                            $settlementStatus,
                    ],
                ];
            }
        }

        /*
         * Vérification de la conformité de tous les devis.
         */
        $quoteQuality = $this->evaluateQuotes(
            $quotes,
            $quoteFiles,
            $selectedQuote,
            self::MINIMUM_QUOTES_REQUIRED,
            self::MINIMUM_SELECTED_QUOTES_REQUIRED
        );

        /*
         * Engagements directement liés à la demande d'achat.
         */
        $commitments = DB::table('financial_commitments')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->orderBy('id')
            ->get();

        $commitmentIds = $commitments
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        /*
         * Consolidation des factures liées aux engagements.
         */
        $invoiceAmount = 0.0;
        $invoiceCount = 0;

        if (!empty($commitmentIds)) {
            $invoiceSummary = DB::table('invoice_providers')
                ->whereIn('financial_commitment_id', $commitmentIds)
                ->selectRaw(
                    'COUNT(*) as invoice_count,
                    COALESCE(SUM(amount), 0) as total'
                )
                ->first();

            $invoiceAmount = (float) $invoiceSummary->total;
            $invoiceCount = (int) $invoiceSummary->invoice_count;
        }

        /*
         * Paiements rattachés au document d'origine.
         */
        $paidAmount = (float) DB::table('payments')
            ->where('document_id', $documentId)
            ->where('status', 'completed')
            ->sum('amount');

        /*
         * Synthèse des justificatifs associés aux échéances
         * du bon de commande.
         */
        $requestOrderId = $requestOrderSummary['id'] ?? null;

        $receiptAllocatedAmount = 0.0;
        $receiptJustifiedAmount = 0.0;
        $receiptCount = 0;
        $receiptsWithFileCount = 0;

        if ($requestOrderId !== null) {
            /*
             * Montant total affecté aux justificatifs.
             */
            $receiptAllocatedAmount = (float) DB::table(
                'purchase_receipt_payment_schedule as pivot'
            )
                ->join(
                    'purchase_receipts as receipt',
                    'receipt.id',
                    '=',
                    'pivot.purchase_receipt_id'
                )
                ->where('receipt.request_order_id', $requestOrderId)
                ->sum('pivot.allocated_amount');

            /*
             * Montant couvert par des justificatifs
             * possédant un fichier RECEIPT valide.
             */
            $receiptJustifiedAmount = (float) DB::table(
                'purchase_receipt_payment_schedule as pivot'
            )
                ->join(
                    'purchase_receipts as receipt',
                    'receipt.id',
                    '=',
                    'pivot.purchase_receipt_id'
                )
                ->where('receipt.request_order_id', $requestOrderId)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('files')
                        ->whereColumn('files.model_id', 'receipt.id')
                        ->where(
                            'files.model_type',
                            PurchaseReceipt::class
                        )
                        ->where('files.type', 'RECEIPT')
                        ->whereNotNull('files.path')
                        ->where('files.path', '<>', '');
                })
                ->sum('pivot.allocated_amount');

            /*
             * Nombre total de justificatifs.
             */
            $receiptCount = DB::table('purchase_receipts')
                ->where('request_order_id', $requestOrderId)
                ->count();

            /*
             * Nombre de justificatifs avec fichier RECEIPT valide.
             */
            $receiptsWithFileCount = DB::table('purchase_receipts as receipt')
                ->where('receipt.request_order_id', $requestOrderId)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('files')
                        ->whereColumn('files.model_id', 'receipt.id')
                        ->where(
                            'files.model_type',
                            PurchaseReceipt::class
                        )
                        ->where('files.type', 'RECEIPT')
                        ->whereNotNull('files.path')
                        ->where('files.path', '<>', '');
                })
                ->count();
        }

        /*
         * Vérification de la couverture des paiements.
         */
        $remainingToJustify = max(
            0,
            $paidAmount - $receiptJustifiedAmount
        );

        $excessJustifiedAmount = max(
            0,
            $receiptJustifiedAmount - $paidAmount
        );

        $paymentFullyJustified = $receiptJustifiedAmount >= $paidAmount;

        /*
         * Calcul des montants des engagements.
         */
        $committedAmount = 0.0;
        $approvedCommitmentAmount = 0.0;
        $commitmentsWithoutApprovedAmount = 0;

        $commitmentSummaries = [];

        foreach ($commitments as $commitment) {
            $initialAmount = (float) $commitment->initial_amount;

            $approvedAmount = $commitment->approved_amount !== null
                ? (float) $commitment->approved_amount
                : null;

            $effectiveAmount = $approvedAmount !== null
                ? $approvedAmount
                : $initialAmount;

            $committedAmount += $effectiveAmount;

            if ($approvedAmount !== null) {
                $approvedCommitmentAmount += $approvedAmount;
            } else {
                $commitmentsWithoutApprovedAmount++;
            }

            $commitmentSummaries[] = [
                'id' => (int) $commitment->id,

                'document_id' => $commitment->document_id !== null
                    ? (int) $commitment->document_id
                    : null,

                'commitment_number' => $commitment->commitment_number,
                'commitment_type' => $commitment->commitment_type,

                'supplier_id' => $commitment->supplier_id !== null
                    ? (int) $commitment->supplier_id
                    : null,

                'supplier_name' => $commitment->supplier_name,
                'title' => $commitment->title,

                'initial_amount' => $initialAmount,
                'approved_amount' => $approvedAmount,
                'effective_amount' => $effectiveAmount,

                'currency' => $commitment->currency,
                'status' => $commitment->status,
            ];
        }

        /*
         * Soldes indicatifs.
         */
        $remainingToInvoice = $committedAmount - $invoiceAmount;
        $remainingToPay = $invoiceAmount - $paidAmount;

        /*
         * Complétude des données financières.
         */
        $financialDataComplete =
            $commitments->isNotEmpty()
            && $commitmentsWithoutApprovedAmount === 0;

        /*
         * Fichiers associés au devis sélectionné.
         */
        $selectedQuoteFiles = $selectedQuote
            ? $quoteFiles->get($selectedQuote->id, collect())
            : collect();

        /*
         * Structure finale du résumé.
         */
        return [
            'success' => true,

            'document_id' => $documentId,

            'purchase_request_id' => (int) $purchaseRequest->id,

            'purchase_request' => [
                'type' => $purchaseRequest->type ?? null,
                'status' => $purchaseRequest->status,
                'priority' => $purchaseRequest->priority,

                'destination_service_id' =>
                    $purchaseRequest->destination_service_id,

                'requested_amount' => round(
                    $requestedAmountTotal,
                    2
                ),

                'approved_amount' => round(
                    $approvedAmountTotal,
                    2
                ),

                'amount' => round(
                    $effectiveAmountTotal,
                    2
                ),

                'items_without_amount' => $itemsWithoutAmount,
            ],

            'items' => [
                'count' => $items->count(),

                'requested_quantity_total' => $requestedQuantityTotal,
                'approved_quantity_total' => $approvedQuantityTotal,
                'quantity' => $effectiveQuantityTotal,

                'requested_amount' => round(
                    $requestedAmountTotal,
                    2
                ),

                'approved_amount' => round(
                    $approvedAmountTotal,
                    2
                ),

                'amount' => round(
                    $effectiveAmountTotal,
                    2
                ),

                'items_without_quantity' => $itemsWithoutQuantity,
                'items_without_amount' => $itemsWithoutAmount,
            ],

            /*
             * Devis sélectionné.
             */
            'selected_quote' => [
                'id' => $selectedQuote
                    ? (int) $selectedQuote->id
                    : null,

                'details' => $selectedQuote,

                'supplier_id' => $selectedQuote
                    && $selectedQuote->supplier_id !== null
                        ? (int) $selectedQuote->supplier_id
                        : null,

                'supplier' => $selectedSupplier,

                'reference' => $selectedQuote
                    ? $selectedQuote->reference
                    : null,

                'amount' => $selectedQuote
                    && $selectedQuote->amount !== null
                        ? (float) $selectedQuote->amount
                        : null,

                'status' => $selectedQuote
                    ? $selectedQuote->status
                    : null,

                'completeness' => $selectedQuote
                    ? $this->evaluateQuoteCompleteness(
                        $selectedQuote,
                        $selectedQuoteFiles
                    )
                    : null,
            ],

            /*
             * Bon de commande associé au devis sélectionné.
             */
            'request_order' => $requestOrderSummary,

            /*
             * Conformité de l'ensemble des devis.
             */
            'quote_quality' => $quoteQuality,

            /*
             * Engagements financiers.
             */
            'commitments' => $commitmentSummaries,

            /*
             * Synthèse financière.
             */
            'totals' => [
                'selected_quote_amount' => $selectedQuote
                    && $selectedQuote->amount !== null
                        ? (float) $selectedQuote->amount
                        : null,

                'committed_amount' => round(
                    $committedAmount,
                    2
                ),

                'approved_commitment_amount' => round(
                    $approvedCommitmentAmount,
                    2
                ),

                'invoiced_amount' => round(
                    $invoiceAmount,
                    2
                ),

                'paid_amount' => round(
                    $paidAmount,
                    2
                ),

                'receipt_allocated_amount' => round(
                    $receiptAllocatedAmount,
                    2
                ),

                'receipt_justified_amount' => round(
                    $receiptJustifiedAmount,
                    2
                ),

                'remaining_to_justify' => round(
                    $remainingToJustify,
                    2
                ),

                'excess_justified_amount' => round(
                    $excessJustifiedAmount,
                    2
                ),

                'remaining_to_invoice' => round(
                    $remainingToInvoice,
                    2
                ),

                'remaining_to_pay' => round(
                    $remainingToPay,
                    2
                ),
            ],

            /*
             * Compteurs.
             */
            'counts' => [
                'commitments' => count($commitmentSummaries),
                'invoices' => $invoiceCount,
                'receipts' => $receiptCount,
                'receipts_with_file' => $receiptsWithFileCount,
            ],

            /*
             * Qualité des données financières.
             */
            'quality' => [
                'financial_data_complete' => $financialDataComplete,

                'commitments_without_approved_amount' =>
                    $commitmentsWithoutApprovedAmount,

                'payment_allocation_available' => false,

                'payment_fully_justified' =>
                    $paymentFullyJustified,

                'payment_justification_complete' =>
                    $remainingToJustify <= 0,

                'payment_justification_missing_amount' => round(
                    $remainingToJustify,
                    2
                ),
            ],
        ];
    }

    /**
     * Vérifie la présence des informations obligatoires d'un devis.
     *
     * @param object $quote
     * @param Collection $quoteFiles
     * @return array
     */
    private function evaluateQuoteCompleteness(
        $quote,
        Collection $quoteFiles
    ): array {
        $fileExists = $quoteFiles->isNotEmpty();

        $checks = [
            'supplier' => $quote->supplier_id !== null,

            'amount' => $quote->amount !== null
                && is_numeric($quote->amount)
                && (float) $quote->amount > 0,

            'files' => $fileExists,

            'reference' => isset($quote->reference)
                && trim((string) $quote->reference) !== '',

            'date' => isset($quote->quote_date)
                && trim((string) $quote->quote_date) !== '',

            'delivery_delay' => isset($quote->delivery_days)
                && is_numeric($quote->delivery_days)
                && (int) $quote->delivery_days > 0,
        ];

        $missingFields = array_keys(
            array_filter(
                $checks,
                function ($isValid) {
                    return !$isValid;
                }
            )
        );

        return [
            'complete' => count($missingFields) === 0,
            'checks' => $checks,
            'missing_fields' => $missingFields,
        ];
    }

    /**
     * Vérifie si une échéance correspond au paiement initial.
     *
     * @param object $schedule
     * @return bool
     */
    private function isInitialPaymentSchedule($schedule): bool
    {
        $trigger = $schedule->paymentTrigger ?? null;

                // throw new Exception(json_encode($trigger->is_initial_payment), 1);


        return $trigger->is_initial_payment;

        if (is_string($trigger)) {
            $trigger = json_decode($trigger, true);
        } elseif (is_object($trigger)) {
            $trigger = (array) $trigger;

        throw new Exception(json_encode($trigger), 1);

        }

        if (!is_array($trigger)) {
            return false;
        }

        $value = $trigger['is_initial_payment'] ?? false;

        if ($value) {
                   
        // throw new Exception(json_encode($trigger), 1);

        }

        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'true'],
            true
        );
    }

    /**
     * Détermine le statut de la régularisation d'un achat.
     *
     * @param bool $initialPaymentFound
     * @param bool $initialPaymentMade
     * @param float $remainingToRegularize
     * @param bool $regularizationCompleted
     * @return array
     */
    private function resolvePurchaseSettlementStatus(
        bool $initialPaymentFound,
        bool $initialPaymentMade,
        float $remainingToRegularize,
        bool $regularizationCompleted
    ): array {
        if (!$initialPaymentFound) {
            return [
                'code' => 'NOT_CONFIGURED',
                'label' => 'Avance initiale non configurée',
                'color' => 'gray',
            ];
        }

        if (!$initialPaymentMade) {
            return [
                'code' => 'WAITING_INITIAL_PAYMENT',
                'label' => 'En attente du paiement initial',
                'color' => 'gray',
            ];
        }

        if ($regularizationCompleted) {
            return [
                'code' => 'SETTLED',
                'label' => 'Régularisée',
                'color' => 'green',
            ];
        }

        if ($remainingToRegularize > 0) {
            return [
                'code' => 'TO_PAY',
                'label' => 'Supplément à payer',
                'color' => 'orange',
            ];
        }

        return [
            'code' => 'TO_REFUND',
            'label' => 'Remboursement à effectuer',
            'color' => 'red',
        ];
    }

    /**
     * Calcule les indicateurs de conformité de tous les devis.
     *
     * @param Collection $quotes
     * @param Collection $quoteFiles
     * @param object|null $selectedQuote
     * @param int $minimumQuotesRequired
     * @param int $minimumSelectedQuotesRequired
     * @return array
     */
    private function evaluateQuotes(
        Collection $quotes,
        Collection $quoteFiles,
        $selectedQuote,
        int $minimumQuotesRequired = 2,
        int $minimumSelectedQuotesRequired = 1
    ): array {
        $quoteSummaries = [];

        $incompleteQuotesCount = 0;

        $selectedQuoteCompleteness = null;

        foreach ($quotes as $quote) {
            $files = $quoteFiles->get($quote->id, collect());

            $completeness = $this->evaluateQuoteCompleteness(
                $quote,
                $files
            );

            if (!$completeness['complete']) {
                $incompleteQuotesCount++;
            }

            $isSelected = $selectedQuote !== null
                && (int) $selectedQuote->id === (int) $quote->id;

            if ($isSelected) {
                $selectedQuoteCompleteness = $completeness;
            }

            $quoteSummaries[] = [
                'id' => (int) $quote->id,

                'reference' => $quote->reference ?? null,

                'supplier_id' => $quote->supplier_id !== null
                    ? (int) $quote->supplier_id
                    : null,

                'amount' => $quote->amount !== null
                    ? (float) $quote->amount
                    : null,

                'status' => $quote->status,

                'is_selected' => $isSelected,

                'complete' => $completeness['complete'],

                'checks' => $completeness['checks'],

                'missing_fields' => $completeness['missing_fields'],
            ];
        }

        $totalQuotes = $quotes->count();

        $selectedQuotesCount = $quotes
            ->filter(function ($quote) {
                return (bool) $quote->is_selected
                    || $quote->status === 'SELECTED';
            })
            ->count();

        $selectedQuoteComplete = $selectedQuoteCompleteness !== null
            && $selectedQuoteCompleteness['complete'];

        $allQuotesComplete = $totalQuotes > 0
            && $incompleteQuotesCount === 0;

        $selectionIsUnique = $selectedQuotesCount === 1;

        return [
            'total_quotes' => $totalQuotes,

            'selected_quotes_count' => $selectedQuotesCount,

            'minimum_quotes_required' => $minimumQuotesRequired,

            'minimum_selected_quotes_required' =>
                $minimumSelectedQuotesRequired,

            'minimum_quotes_satisfied' =>
                $totalQuotes >= $minimumQuotesRequired,

            'minimum_selected_quotes_satisfied' =>
                $selectedQuotesCount >= $minimumSelectedQuotesRequired,

            'selection_is_unique' => $selectionIsUnique,

            'all_quotes_complete' => $allQuotesComplete,

            'incomplete_quotes_count' => $incompleteQuotesCount,

            'selected_quote_complete' => $selectedQuoteComplete,

            'selected_quote_completeness' =>
                $selectedQuoteCompleteness
                    ? $selectedQuoteCompleteness['checks']
                    : null,

            'quotes' => $quoteSummaries,
        ];
    }

    /**
     * Retourne une structure stable lorsqu'aucune demande d'achat
     * n'est associée au document.
     *
     * @param int $documentId
     * @return array
     */
    private function emptySummary(int $documentId): array
    {
        return [
            'success' => true,

            'document_id' => $documentId,

            'purchase_request_id' => null,

            'purchase_request' => null,

            'items' => [
                'count' => 0,
                'requested_quantity_total' => 0,
                'approved_quantity_total' => 0,
                'quantity' => 0,
                'requested_amount' => 0,
                'approved_amount' => 0,
                'amount' => 0,
                'items_without_quantity' => 0,
                'items_without_amount' => 0,
            ],

            'selected_quote' => null,

            'request_order' => null,

            'quote_quality' => [
                'total_quotes' => 0,

                'selected_quotes_count' => 0,

                'minimum_quotes_required' =>
                    self::MINIMUM_QUOTES_REQUIRED,

                'minimum_selected_quotes_required' =>
                    self::MINIMUM_SELECTED_QUOTES_REQUIRED,

                'minimum_quotes_satisfied' => false,

                'minimum_selected_quotes_satisfied' => false,

                'selection_is_unique' => false,

                'all_quotes_complete' => false,

                'incomplete_quotes_count' => 0,

                'selected_quote_complete' => false,

                'selected_quote_completeness' => null,

                'quotes' => [],
            ],

            'commitments' => [],

            'totals' => [
                'selected_quote_amount' => null,
                'committed_amount' => 0,
                'approved_commitment_amount' => 0,
                'invoiced_amount' => 0,
                'paid_amount' => 0,
                'remaining_to_invoice' => 0,
                'remaining_to_pay' => 0,
                'receipt_allocated_amount' => 0,
                'receipt_justified_amount' => 0,
                'remaining_to_justify' => 0,
                'excess_justified_amount' => 0,
            ],

            'counts' => [
                'commitments' => 0,
                'invoices' => 0,
                'receipts' => 0,
                'receipts_with_file' => 0,
            ],

            'quality' => [
                'financial_data_complete' => false,

                'commitments_without_approved_amount' => 0,

                'payment_allocation_available' => false,

                'payment_fully_justified' => false,

                'payment_justification_complete' => false,

                'payment_justification_missing_amount' => 0,
            ],
        ];
    }
}