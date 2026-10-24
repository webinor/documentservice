<?php

namespace App\Services\Financial;

use App\Services\Supplier\SupplierServiceClient;
use Illuminate\Support\Facades\DB;

class PurchaseFinancialSummaryService
{

/**
 * @var SupplierServiceClient
 */
private $supplierServiceClient;

/**
 * @param SupplierServiceClient $supplierServiceClient
 */
public function __construct(
    SupplierServiceClient $supplierServiceClient
) {
    $this->supplierServiceClient = $supplierServiceClient;
}
    /**
     * Construit le résumé financier d'une demande d'achat GED.
     *
     * Les paiements sont lus dans la table payments, avec le document
     * d'origine comme référence. Les factures sont consolidées à partir
     * des engagements auxquels elles sont explicitement rattachées.
     *
     * Attention :
     * - un devis n'est pas un engagement ;
     * - un engagement n'est pas une facture ;
     * - une facture n'est pas un paiement ;
     * - les paiements au niveau de la demande ne sont pas ventilés
     *   artificiellement entre plusieurs engagements.
     *
     * @param int $documentId Identifiant du document GED
     * @return array
     */
    public function build(int $documentId): array
    {
        $purchaseRequest = DB::table('purchase_requests')
            ->where('document_id', $documentId)
            ->first();

        if (!$purchaseRequest) {
            return $this->emptySummary($documentId);
        }

        $items = DB::table('purchase_request_items')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->get();

        $selectedQuote = DB::table('purchase_supplier_quotes')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->where(function ($query) {
                $query->where('is_selected', true)
                    ->orWhere('status', 'SELECTED');
            })
            ->orderByDesc('id')
            ->first();

        /*
 * Enrichissement du devis retenu avec les informations du fournisseur.
 * L'appel n'est effectué que si un devis a été sélectionné.
 */
$selectedSupplier = $selectedQuote
    ? $this->supplierServiceClient->getSupplier(
        $selectedQuote->supplier_id !== null
            ? (int) $selectedQuote->supplier_id
            : null
    )
    : null;

        /*
         * Les engagements sont directement liés à la demande d'achat.
         * Cette relation permet également d'enregistrer des engagements
         * futurs qui ne correspondent pas à un bon de commande.
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

        $invoiceAmount = 0.0;
        $invoiceCount = 0;

        if (!empty($commitmentIds)) {
            $invoiceSummary = DB::table('invoice_providers')
                ->whereIn('financial_commitment_id', $commitmentIds)
                ->selectRaw(
                    'COUNT(*) as invoice_count, COALESCE(SUM(amount), 0) as total'
                )
                ->first();

            $invoiceAmount = (float) $invoiceSummary->total;
            $invoiceCount = (int) $invoiceSummary->invoice_count;
        }

        /*
         * Les paiements sont rattachés au document d'origine.
         *
         * Nous ne les répartissons pas entre les engagements, car le
         * schéma connu ne permet pas encore d'attribuer chaque paiement
         * à un engagement précis.
         */
        $paidAmount = (float) DB::table('payments')
            ->where('document_id', $documentId)
            ->where('status', 'completed')
            ->sum('amount');

        /*
         * On utilise le montant approuvé lorsqu'il existe.
         * Sinon, le montant initial sert de valeur informative, mais
         * il ne doit pas être présenté comme un montant approuvé.
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
         * Les montants négatifs ne sont pas forcés à zéro :
         * un dépassement peut révéler un problème à examiner.
         */
        $remainingToInvoice = $committedAmount - $invoiceAmount;
        $remainingToPay = $invoiceAmount - $paidAmount;

        /*
         * La synthèse est considérée comme complète uniquement si :
         * - chaque engagement possède un montant approuvé ;
         * - chaque facture de la demande peut être associée à un
         *   engagement.
         *
         * Ce premier contrôle couvre les factures liées aux engagements.
         * Les factures non rattachées nécessitent un contrôle séparé
         * si l'application dispose d'un lien fiable vers la demande.
         */
        // $financialDataComplete = $commitmentsWithoutApprovedAmount === 0;
        $financialDataComplete =
    $commitments->isNotEmpty()
    && $commitmentsWithoutApprovedAmount === 0;

        return [
            'document_id' => $documentId,
            'purchase_request_id' => (int) $purchaseRequest->id,

            'purchase_request' => [
                'status' => $purchaseRequest->status,
                'priority' => $purchaseRequest->priority,
                'destination_service_id' => $purchaseRequest->destination_service_id,
            ],

            'items' => [
                'count' => $items->count(),
                'requested_quantity_total' => (float) $items->sum(
                    'requested_quantity'
                ),
                'approved_quantity_total' => (float) $items->sum(
                    'approved_quantity'
                ),
            ],

            'selected_quote' => [
                'id' => $selectedQuote ? (int) $selectedQuote->id : null,
                'supplier_id' => $selectedQuote
                    && $selectedQuote->supplier_id !== null
                        ? (int) $selectedQuote->supplier_id
                        : null,

                 // Informations récupérées depuis le microservice Supplier.
                'supplier' => $selectedSupplier,
                'reference' => $selectedQuote
                    ? $selectedQuote->reference
                    : null,
                'amount' => $selectedQuote
                    ? (float) $selectedQuote->amount
                    : null,
                'status' => $selectedQuote
                    ? $selectedQuote->status
                    : null,
            ],

            'commitments' => $commitmentSummaries,

            'totals' => [
                /*
                 * Montant du devis retenu, informatif.
                 * Il n'est pas ajouté au montant engagé.
                 */
                'selected_quote_amount' => $selectedQuote
                    ? (float) $selectedQuote->amount
                    : null,

                /*
                 * Montant total effectif des engagements :
                 * montant approuvé, sinon montant initial.
                 */
                'committed_amount' => round($committedAmount, 2),

                /*
                 * Total des seuls montants explicitement approuvés.
                 */
                'approved_commitment_amount' => round(
                    $approvedCommitmentAmount,
                    2
                ),

                'invoiced_amount' => round($invoiceAmount, 2),
                'paid_amount' => round($paidAmount, 2),

                /*
                 * Positif : engagement restant à facturer.
                 * Négatif : facturation supérieure à l'engagement.
                 */
                'remaining_to_invoice' => round($remainingToInvoice, 2),

                /*
                 * Positif : factures restant à régler.
                 * Négatif : paiements supérieurs aux factures connues.
                 *
                 * Ce calcul est indicatif tant que les paiements ne sont
                 * pas ventilés par engagement/facture.
                 */
                'remaining_to_pay' => round($remainingToPay, 2),
            ],

            'counts' => [
                'commitments' => count($commitmentSummaries),
                'invoices' => $invoiceCount,
            ],

            'quality' => [
                'financial_data_complete' => $financialDataComplete,
                'commitments_without_approved_amount' =>
                    $commitmentsWithoutApprovedAmount,
                'payment_allocation_available' => false,
            ],
        ];
    }

    /**
     * Retourne une structure stable lorsqu'aucune demande n'existe.
     *
     * @param int $documentId
     * @return array
     */
    private function emptySummary(int $documentId): array
    {
        return [
            'document_id' => $documentId,
            'purchase_request_id' => null,
            'purchase_request' => null,
            'items' => [
                'count' => 0,
                'requested_quantity_total' => 0,
                'approved_quantity_total' => 0,
            ],
            'selected_quote' => null,
            'commitments' => [],
            'totals' => [
                'selected_quote_amount' => null,
                'committed_amount' => 0,
                'approved_commitment_amount' => 0,
                'invoiced_amount' => 0,
                'paid_amount' => 0,
                'remaining_to_invoice' => 0,
                'remaining_to_pay' => 0,
            ],
            'counts' => [
                'commitments' => 0,
                'invoices' => 0,
            ],
            'quality' => [
                'financial_data_complete' => false,
                'commitments_without_approved_amount' => 0,
                'payment_allocation_available' => false,
            ],
        ];
    }
}