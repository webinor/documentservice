<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseSupplierQuoteRequest;
use App\Http\Requests\UpdatePurchaseSupplierQuoteRequest;
use App\Models\Misc\File;
use App\Models\PurchaseSupplierQuote;
use App\Services\Supplier\SupplierServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PurchaseSupplierQuoteController extends Controller
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
     * Liste les devis d'une demande d'achat.
     *
     * GET /purchase-requests/{purchaseRequestId}/quotes
     *
     * @param int $purchaseRequestId
     * @return JsonResponse
     */
    public function index($purchaseRequestId): JsonResponse
    {

  

        $quotes = PurchaseSupplierQuote::query()
            ->where('purchase_request_id', $purchaseRequestId)
            ->with('files')
            ->orderBy('created_at')
            ->get();

        // 1. Collecter les identifiants des fournisseurs.
$supplierIds = $quotes
    ->pluck('supplier_id')
    ->filter()
    ->unique()
    ->values()
    ->all();

// 2. Récupérer tous les fournisseurs en un seul appel HTTP.
$suppliersById = $this->supplierServiceClient
    ->getSuppliersByIds($supplierIds);

// 3. Enrichir chaque devis.
$quotes = $quotes->map(function ($quote) use ($suppliersById) {
    $supplierId = (int) $quote->supplier_id;

    $quote->supplier = $suppliersById[$supplierId] ?? null;

    return $quote;
});

        return response()->json([
            'success' => true,
            'message' => 'Liste des devis récupérée avec succès.',
            'data' => $quotes,
        ], Response::HTTP_OK);
    }

    /**
     * Affiche un devis appartenant à une demande d'achat.
     *
     * GET /purchase-requests/{purchaseRequestId}/quotes/{quoteId}
     *
     * @param int $purchaseRequestId
     * @param int $quoteId
     * @return JsonResponse
     */
    public function show($purchaseRequestId, $quoteId): JsonResponse
    {
        $quote = $this->findQuote($purchaseRequestId, $quoteId);

        return response()->json([
            'success' => true,
            'message' => 'Devis récupéré avec succès.',
            'data' => $quote->load('file'),
        ], Response::HTTP_OK);
    }

/**
 * Crée un devis vide associé à une demande d'achat.
 *
 * POST /purchase-requests/{purchaseRequestId}/quotes
 *
 * Seuls les éléments nécessaires à l'initialisation du devis
 * sont renseignés. Les informations du fournisseur et du devis
 * seront complétées ultérieurement via update().
 *
 * @param StorePurchaseSupplierQuoteRequest $request
 * @param int $purchaseRequestId
 * @return JsonResponse
 */
public function store(
    StorePurchaseSupplierQuoteRequest $request,
    $purchaseRequestId
): JsonResponse {
    try {
        $quote = DB::transaction(function () use ($purchaseRequestId, $request) {
            return PurchaseSupplierQuote::create([
                'purchase_request_id' => $purchaseRequestId,
                'is_selected' => false,
                'status' => 'PENDING',
                'created_by' =>$request->get("user")["id"],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Devis initialisé avec succès.',
            'data' => $quote->fresh()->load('files'),
        ], Response::HTTP_CREATED);

    } catch (Throwable $exception) {
        report($exception);

        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la création du devis.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    /**
     * Modifie un devis existant.
     *
     * PUT /purchase-requests/{purchaseRequestId}/quotes/{quoteId}
     *
     * @param UpdatePurchaseSupplierQuoteRequest $request
     * @param int $purchaseRequestId
     * @param int $quoteId
     * @return JsonResponse
     */
    public function update(
        UpdatePurchaseSupplierQuoteRequest $request,
        $purchaseRequestId,
        $quoteId
    )//: JsonResponse 
    {
        $quote = $this->findQuote($purchaseRequestId, $quoteId);

        // Un devis déjà retenu ne doit pas être modifié sans
        // une règle métier explicite autorisant cette opération.
        // if ($quote->status === 'SELECTED') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Un devis sélectionné ne peut pas être modifié.',
        //     ], Response::HTTP_UNPROCESSABLE_ENTITY);
        // }
// return
        $data = $request->validated();

        $allowedFields = [
            'supplier_id',
            'reference',
            'amount',
            'comment',
            'selection_reason',
            'quote_date',
            'delivery_days'
        ];

        $updates = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        try {
            DB::transaction(function () use (
                $quote,
                $updates,
                $request
            ) {
                if (!empty($updates)) {
                    $quote->update($updates);
                }

                // Si un nouveau fichier est envoyé, il remplace
                // le fichier précédemment associé au devis.
                if ($request->hasFile('attachment')) {
                    $this->replaceFile(
                        $quote,
                        $request->file('attachment')
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Devis modifié avec succès.',
                'data' => $quote->fresh()->load('files'),
            ], Response::HTTP_OK);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la modification du devis : '.$exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime un devis.
     *
     * DELETE /purchase-requests/{purchaseRequestId}/quotes/{quoteId}
     *
     * @param int $purchaseRequestId
     * @param int $quoteId
     * @return JsonResponse
     */
    public function destroy($purchaseRequestId, $quoteId): JsonResponse
    {
        $quote = $this->findQuote($purchaseRequestId, $quoteId);

        if ($quote->status === 'SELECTED') {
            return response()->json([
                'success' => false,
                'message' => 'Un devis sélectionné ne peut pas être supprimé.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::transaction(function () use ($quote) {
                $this->deleteAssociatedFile($quote);

                $quote->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Devis supprimé avec succès.',
            ], Response::HTTP_OK);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression du devis.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Sélectionne un devis comme devis retenu.
     *
     * PUT /purchase-requests/{purchaseRequestId}/quotes/{quoteId}/select
     *
     * Un seul devis peut être sélectionné par demande d'achat.
     *
     * @param int $purchaseRequestId
     * @param int $quoteId
     * @return JsonResponse
     */
    public function select($purchaseRequestId, $quoteId): JsonResponse
    {
        try {
            $quote = DB::transaction(function () use (
                $purchaseRequestId,
                $quoteId
            ) {
                /*
                 * Verrouille la demande d'achat logique en verrouillant
                 * les devis existants correspondants.
                 *
                 * Pour une garantie complète en cas de concurrence,
                 * il est préférable de verrouiller également la ligne
                 * de la demande d'achat elle-même.
                 */
                $quotes = PurchaseSupplierQuote::query()
                    ->where('purchase_request_id', $purchaseRequestId)
                    ->lockForUpdate()
                    ->get();

                $quote = $quotes->first(function ($item) use ($quoteId) {
                    return (int) $item->id === (int) $quoteId;
                });

                if (!$quote) {
                    abort(Response::HTTP_NOT_FOUND, 'Devis introuvable.');
                }

                if ($quote->status === 'REJECTED') {
                    throw ValidationException::withMessages([
                        'quote' => [
                            'Un devis rejeté ne peut pas être sélectionné.',
                        ],
                    ]);
                }

                // Désélectionne les autres devis de la demande.
                PurchaseSupplierQuote::query()
                    ->where('purchase_request_id', $purchaseRequestId)
                    ->where('id', '<>', $quote->id)
                    ->update([
                        'is_selected' => false,
                        'status' => 'PENDING',
                    ]);

                // Sélectionne le devis retenu.
                $quote->update([
                    'is_selected' => true,
                    'status' => 'SELECTED',
                ]);

                return $quote;
            });

            return response()->json([
                'success' => true,
                'message' => 'Devis sélectionné avec succès.',
                'data' => $quote->fresh()->load('files'),
            ], Response::HTTP_OK);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la sélection du devis : '.$exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Recherche un devis appartenant à la demande d'achat indiquée.
     *
     * Empêche de récupérer un devis associé à une autre demande.
     *
     * @param int $purchaseRequestId
     * @param int $quoteId
     * @return PurchaseSupplierQuote
     */
    private function findQuote(
        $purchaseRequestId,
        $quoteId
    ): PurchaseSupplierQuote {
        return PurchaseSupplierQuote::query()
            ->where('purchase_request_id', $purchaseRequestId)
            ->where('id', $quoteId)
            ->firstOrFail();
    }

    /**
     * Associe un fichier au devis.
     *
     * Le fichier est stocké sur le disque public Laravel.
     * Le modèle File conserve ses métadonnées et son association
     * polymorphique avec PurchaseSupplierQuote.
     *
     * @param PurchaseSupplierQuote $quote
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @return File
     */
    private function attachFile(
        PurchaseSupplierQuote $quote,
        $uploadedFile
    ): File {
        $path = $uploadedFile->store('purchase-supplier-quotes', 'public');

        if (!$path) {
            throw new \RuntimeException(
                'Impossible de stocker le fichier du devis.'
            );
        }

        try {
            return $quote->files()->create([
                'path' => $path,
                'size' => $uploadedFile->getSize(),
                'type' => $uploadedFile->getMimeType(),
            ]);
        } catch (Throwable $exception) {
            // Le fichier ne doit pas rester orphelin si l'insertion échoue.
            Storage::disk('public')->delete($path);

            throw $exception;
        }
    }

    /**
     * Remplace le fichier associé au devis.
     *
     * @param PurchaseSupplierQuote $quote
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @return void
     */
    private function replaceFile(
        PurchaseSupplierQuote $quote,
        $uploadedFile
    ): void {
        $this->deleteAssociatedFile($quote);

        $this->attachFile($quote, $uploadedFile);
    }

    /**
     * Supprime le fichier associé et ses métadonnées.
     *
     * @param PurchaseSupplierQuote $quote
     * @return void
     */
    private function deleteAssociatedFile(
        PurchaseSupplierQuote $quote
    ): void {
        $file = $quote->file;

        if (!$file) {
            return;
        }

        /*
         * Suppression du fichier physique.
         * La transaction SQL ne peut pas annuler une suppression
         * sur le système de fichiers : une stratégie de stockage
         * plus robuste peut être nécessaire en production.
         */
        if (!empty($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        $file->delete();
    }
}