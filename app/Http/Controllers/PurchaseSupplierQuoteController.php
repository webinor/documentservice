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
use Illuminate\Support\Facades\Log;
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
            'data' => $quote->load('files'),
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
            'delivery_days',
            'client_id'
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

           
                /*
 * Compatibilité avec l'ancien formulaire.
 * Un nouveau fichier est ajouté sans supprimer les fichiers existants.
 */
if ($request->hasFile('attachment')) {
    $this->attachFile(
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

        // if ($quote->status === 'SELECTED') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Un devis sélectionné ne peut pas être supprimé.',
        //     ], Response::HTTP_UNPROCESSABLE_ENTITY);
        // }

        try {
            DB::transaction(function () use ($quote) {
                foreach ($quote->files as $file) {
    if (!empty($file->path)) {
        Storage::disk('public')->delete($file->path);
    }

    $file->delete();
}

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


    /**
 * Ajoute une ou plusieurs pièces jointes à un devis fournisseur.
 *
 * POST /purchase-requests/{purchaseRequestId}/quotes/{quoteId}/files
 *
 * Le champ attendu est files[].
 *
 * @param Request $request
 * @param int|string $purchaseRequestId
 * @param int|string $quoteId
 * @return JsonResponse
 */
public function storeFiles(
    Request $request,
    $purchaseRequestId,
    $quoteId
): JsonResponse {
    $quote = $this->findQuote($purchaseRequestId, $quoteId);


    Log::debug('DOCUMENTS - Fichiers reçus', [
    'has_files' => $request->hasFile('files'),
    'file_keys' => array_keys($request->allFiles()),
    'files_is_array' => is_array($request->file('files')),
    'files_count' => is_array($request->file('files'))
        ? count($request->file('files'))
        : ($request->hasFile('files') ? 1 : 0),
    'files' => collect(
        is_array($request->file('files'))
            ? $request->file('files')
            : array_filter([$request->file('files')])
    )->map(function ($file) {
        return [
            'name' => $file->getClientOriginalName(),
            'valid' => $file->isValid(),
            'size' => $file->getSize(),
        ];
    })->values()->all(),
]);

    $validated = $request->validate([
        'files' => 'required|array|min:1|max:10',
        'files.*' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:20480',
    ]);

    $storedFiles = [];

    try {
        foreach ($validated['files'] as $uploadedFile) {
            $storedFiles[] = $this->attachFile($quote, $uploadedFile);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pièce(s) jointe(s) enregistrée(s) avec succès.',
            'data' => $quote->fresh()->load('files'),
        ], Response::HTTP_CREATED);

    } catch (Throwable $exception) {
        report($exception);

        /*
         * Nettoyage des fichiers créés pendant cette requête
         * si l'ajout d'une des pièces jointes échoue.
         */
        foreach ($storedFiles as $file) {
            if (!empty($file->path)) {
                Storage::disk('public')->delete($file->path);
            }

            $file->delete();
        }

        return response()->json([
            'success' => false,
            'message' => 'Impossible d’enregistrer les pièces jointes.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

/**
 * Affiche une pièce jointe dans le navigateur.
 *
 * @param int|string $purchaseRequestId
 * @param int|string $quoteId
 * @param int|string $fileId
 * @return Response|\Illuminate\Http\JsonResponse
 */
public function viewFile(
    $purchaseRequestId,
    $quoteId,
    $fileId
) {
    $quote = $this->findQuote($purchaseRequestId, $quoteId);

    $file = $quote->files()
        ->where('id', $fileId)
        ->firstOrFail();

    if (
        empty($file->path) ||
        !Storage::disk('public')->exists($file->path)
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Le fichier demandé est introuvable.',
        ], Response::HTTP_NOT_FOUND);
    }

    $mimeType = Storage::disk('public')->mimeType($file->path)
        ?: 'application/octet-stream';

    $filename = basename($file->path);

    return Storage::disk('public')->response(
        $file->path,
        $filename,
        [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ],
        'inline'
    );
}

/**
 * Télécharge une pièce jointe.
 *
 * @param int|string $purchaseRequestId
 * @param int|string $quoteId
 * @param int|string $fileId
 * @return Response|\Illuminate\Http\JsonResponse
 */
public function downloadFile(
    $purchaseRequestId,
    $quoteId,
    $fileId
) {
    $quote = $this->findQuote($purchaseRequestId, $quoteId);

    $file = $quote->files()
        ->where('id', $fileId)
        ->firstOrFail();

    if (
        empty($file->path) ||
        !Storage::disk('public')->exists($file->path)
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Le fichier demandé est introuvable.',
        ], Response::HTTP_NOT_FOUND);
    }

    return Storage::disk('public')->download(
        $file->path,
        basename($file->path)
    );
}

/**
 * Supprime une pièce jointe d'un devis fournisseur.
 *
 * @param int|string $purchaseRequestId
 * @param int|string $quoteId
 * @param int|string $fileId
 * @return JsonResponse
 */
public function destroyFile(
    $purchaseRequestId,
    $quoteId,
    $fileId
): JsonResponse {
    $quote = $this->findQuote($purchaseRequestId, $quoteId);

    $file = $quote->files()
        ->where('id', $fileId)
        ->firstOrFail();

    try {
        DB::transaction(function () use ($file) {
            $file->delete();
        });

        /*
         * Le fichier physique est supprimé après la transaction SQL.
         * Une erreur de stockage ne doit pas rétablir les métadonnées
         * déjà supprimées en base.
         */
        if (!empty($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pièce jointe supprimée avec succès.',
            'data' => [
                'id' => (int) $fileId,
            ],
        ], Response::HTTP_OK);

    } catch (Throwable $exception) {
        report($exception);

        return response()->json([
            'success' => false,
            'message' => 'Impossible de supprimer la pièce jointe.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

}