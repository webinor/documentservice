<?php

namespace App\Services\Financial;

use App\Models\PurchaseSupplierQuote;
use App\Services\Supplier\SupplierServiceClient;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseFinancialSummaryService
{
    /**
     * Nombre minimum de devis attendus.
     *
     * Cette valeur pourra ensuite provenir de la configuration
     * ou des règles du workflow.
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
     * Le résumé contient :
     * - Les informations générales de la demande.
     * - Les quantités des articles.
     * - Le devis sélectionné et sa conformité.
     * - La conformité de tous les devis.
     * - Les engagements financiers.
     * - Les factures liées aux engagements.
     * - Les paiements du document d'origine.
     *
     * @param int $documentId
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

    // Totaux des quantités
    $requestedQuantityTotal += (int) ($requestedQuantity ?? 0);
    $approvedQuantityTotal += (int) ($approvedQuantity ?? 0);

    $effectiveQuantity = $approvedQuantity !== null
        ? (int) $approvedQuantity
        : (int) ($requestedQuantity ?? 0);

    $effectiveQuantityTotal += $effectiveQuantity;

    if ($requestedQuantity === null && $approvedQuantity === null) {
        $itemsWithoutQuantity++;
    }

    // Montant total demandé : quantité demandée × prix unitaire demandé
    if ($requestedQuantity !== null && $requestedUnitPrice !== null) {
        $requestedAmountTotal +=
            (float) $requestedQuantity * (float) $requestedUnitPrice;
    }

    // Montant total approuvé : quantité approuvée × prix unitaire approuvé
    if ($approvedQuantity !== null && $approvedUnitPrice !== null) {
        $approvedAmountTotal +=
            (float) $approvedQuantity * (float) $approvedUnitPrice;
    }

    // Valeurs retenues : les valeurs approuvées priment sur les valeurs demandées
    $effectiveUnitPrice = $approvedUnitPrice !== null
        ? (float) $approvedUnitPrice
        : ($requestedUnitPrice !== null
            ? (float) $requestedUnitPrice
            : null);

    if (
        $effectiveQuantity > 0 &&
        $effectiveUnitPrice !== null
    ) {
        $effectiveAmountTotal +=
            $effectiveQuantity * $effectiveUnitPrice;
    } else {
        $itemsWithoutAmount++;
    }
}

        /*
         * Récupération de tous les devis de la demande.
         *
         * On conserve les devis non sélectionnés pour pouvoir
         * vérifier leur complétude et leur nombre.
         */
        $quotes = DB::table('purchase_supplier_quotes')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->orderBy('id')
            ->get();

   /*
 * Récupération groupée des fichiers associés aux devis.
 *
 * La relation polymorphique utilise :
 * - model_type : classe du modèle associé ;
 * - model_id   : identifiant du devis.
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
         *
         * La règle actuelle considère qu'un devis est sélectionné
         * lorsque is_selected est vrai ou que son statut vaut SELECTED.
         *
         * Si plusieurs devis correspondent, le plus récent est retenu
         * pour la présentation du résumé. Une sélection multiple reste
         * détectable dans quote_quality.
         */
        $selectedQuote = $quotes
            ->filter(function ($quote) {
                return (bool) $quote->is_selected
                    || $quote->status === 'SELECTED';
            })
            ->sortByDesc('id')
            ->first();

        /*
         * Informations du fournisseur du devis sélectionné.
         *
         * L'appel au microservice n'est effectué que si un devis
         * et un identifiant fournisseur existent.
         */
        $selectedSupplier = null;

        if (
            $selectedQuote
            && $selectedQuote->supplier_id !== null
        ) {
            $selectedSupplier = $this->supplierServiceClient->getSupplier(
                (int) $selectedQuote->supplier_id
            );
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
         *
         * Ils ne sont pas répartis artificiellement entre les
         * différents engagements.
         */
        $paidAmount = (float) DB::table('payments')
            ->where('document_id', $documentId)
            ->where('status', 'completed')
            ->sum('amount');

        /*
         * Calcul des montants des engagements.
         *
         * Le montant approuvé est prioritaire lorsqu'il existe.
         * Sinon, le montant initial est utilisé comme valeur effective.
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
         * La synthèse financière est complète si au moins un
         * engagement existe et si tous les engagements ont un montant
         * approuvé.
         *
         * Cette règle conserve le comportement métier actuel.
         */
        $financialDataComplete =
            $commitments->isNotEmpty()
            && $commitmentsWithoutApprovedAmount === 0;


        /*
 * Fichiers associés au devis sélectionné.
 *
 * Les fichiers ont déjà été récupérés et regroupés
 * par model_id dans la collection $quoteFiles.
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

    // Montant total initialement demandé.
    'requested_amount' => round($requestedAmountTotal, 2),

    // Total des montants approuvés renseignés.
    'approved_amount' => round($approvedAmountTotal, 2),

    // Montant effectif : approuvé si disponible, sinon demandé.
    'amount' => round($effectiveAmountTotal, 2),

    // Nombre d'articles sans montant demandé ni approuvé.
    'items_without_amount' => $itemsWithoutAmount,
],

           'items' => [
    'count' => $items->count(),

    // Quantités
    'requested_quantity_total' => $requestedQuantityTotal,
    'approved_quantity_total' => $approvedQuantityTotal,
    'quantity' => $effectiveQuantityTotal,

    // Montants calculés à partir des prix unitaires
    'requested_amount' => round($requestedAmountTotal, 2),
    'approved_amount' => round($approvedAmountTotal, 2),
    'amount' => round($effectiveAmountTotal, 2),

    // Anomalies
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

                /*
                 * Résultat du contrôle de complétude du devis retenu.
                 */
                'completeness' => $selectedQuote
                    ? $this->evaluateQuoteCompleteness($selectedQuote , $selectedQuoteFiles)
                    : null,
            ],

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

                'committed_amount' => round($committedAmount, 2),

                'approved_commitment_amount' => round(
                    $approvedCommitmentAmount,
                    2
                ),

                'invoiced_amount' => round($invoiceAmount, 2),

                'paid_amount' => round($paidAmount, 2),

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
            ],

            /*
             * Qualité des données financières.
             */
            'quality' => [
                'financial_data_complete' => $financialDataComplete,

                'commitments_without_approved_amount' =>
                    $commitmentsWithoutApprovedAmount,

                'payment_allocation_available' => false,
            ],
        ];
    }

   
/**
 * Vérifie la présence des informations obligatoires d'un devis.
 *
 * Champs contrôlés :
 * - Fournisseur.
 * - Montant positif.
 * - Fichier associé et existant.
 * - Référence.
 * - Date du devis.
 * - Délai de livraison positif.
 *
 * Le fichier est récupéré via la relation polymorphique files().
 *
 * @param object $quote
 * @return array
 */
private function evaluateQuoteCompleteness(
    $quote,
    Collection $quoteFiles 
): array {
    /*
     * Vérification de l'existence d'un fichier associé au devis.
     *
     * La relation files() utilise la relation polymorphique
     * entre PurchaseSupplierQuote et File.
     *
     * On vérifie également que le chemin du fichier est renseigné.
     */
//    $fileExists = $quoteFiles !== null
//     ? $quoteFiles->has($quote->id)
//     : DB::table('files')
//         ->where('model_type', PurchaseSupplierQuote::class)
//         ->where('model_id', $quote->id)
//         ->whereNotNull('path')
//         ->where('path', '<>', '')
//         ->exists();
    /*
     * Les fichiers du devis ont déjà été récupérés
     * et transmis à cette méthode.
     */
    $fileExists = $quoteFiles->isNotEmpty();


    // throw new Exception(json_encode($quoteFiles), 1);
    // throw new Exception(json_encode($quote), 1);
    // throw new Exception(json_encode($fileExists), 1);


    /*
     * Vérification de chaque champ obligatoire.
     */
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

    /*
     * Liste des champs qui ne satisfont pas les contrôles.
     */
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
     * Calcule les indicateurs de conformité de tous les devis.
     *
     * @param Collection $quotes
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


        /*
         * Évaluation individuelle de chaque devis.
         */
        foreach ($quotes as $quote) {

        /*
     * Récupération des fichiers associés à ce devis.
     *
     * get() retourne une collection vide si aucun fichier
     * n'est associé au devis.
     */
    $files = $quoteFiles->get($quote->id, collect());

    // throw new Exception(json_encode($files), 1);
    

    /*
     * Évaluation de la complétude du devis avec ses fichiers.
     */
     $completeness = $this->evaluateQuoteCompleteness($quote , $files);

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

        /*
         * Nombre total de devis.
         */
        $totalQuotes = $quotes->count();

        /*
         * Nombre de devis marqués comme sélectionnés.
         *
         * Ce comptage détecte aussi une éventuelle sélection multiple.
         */
        $selectedQuotesCount = $quotes
            ->filter(function ($quote) {
                return (bool) $quote->is_selected
                    || $quote->status === 'SELECTED';
            })
            ->count();

        /*
         * Un devis sélectionné est complet uniquement si tous
         * ses champs obligatoires sont valides.
         */
        $selectedQuoteComplete = $selectedQuoteCompleteness !== null
            && $selectedQuoteCompleteness['complete'];

        /*
         * Tous les devis sont complets si aucun devis n'est incomplet
         * et qu'au moins un devis existe.
         */
        $allQuotesComplete = $totalQuotes > 0
            && $incompleteQuotesCount === 0;

        /*
         * Contrôle de sélection unique.
         */
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

            /*
             * Liste détaillée permettant d'identifier les devis
             * incomplets et les champs manquants.
             */
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
            ],

            'selected_quote' => null,

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