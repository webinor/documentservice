<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionExpense;
use App\Models\MissionReceipt;
use App\Models\Misc\File;
use App\Services\Common\FileManager;
use App\Services\Document\DocumentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MissionReceiptController extends Controller
{
    private DocumentService $documentService;
    private FileManager $fileManager;

    public function __construct(
        DocumentService $documentService,
        FileManager $fileManager
    ) {
        $this->documentService = $documentService;
        $this->fileManager = $fileManager;
    }

    /**
     * Récupère la mission associée au document.
     *
     * Le document peut être identifié par son ID ou son UUID.
     */
    private function getMission(string $documentIdentifier): Mission
    {
        $document = $this->documentService->getDoc(
            $documentIdentifier
        );

        $document->load('mission');

        if (!$document->mission) {
            abort(404, 'Aucune mission associée à ce document.');
        }

        return $document->mission;
    }

    /**
     * Récupère un justificatif appartenant à la mission.
     */
    private function getReceipt(
        Mission $mission,
        string $receiptCode
    ): MissionReceipt {
        return MissionReceipt::where('mission_id', $mission->id)
            ->where('code', $receiptCode)
            ->firstOrFail();
    }

    /**
     * Formate un justificatif pour la réponse API.
     */

    /**
 * Formate un justificatif de mission pour la réponse API.
 */
private function formatReceipt(MissionReceipt $receipt): array
{
    $receipt->load([
        'file',
        'expenses.expense_category',
    ]);

    $file = $receipt->file;

    $baseUrl = rtrim(config('app.url'), '/');

    $fileData = null;

    if ($file) {

        /*
        |--------------------------------------------------------------------------
        | URL ORIGINAL
        |--------------------------------------------------------------------------
        */

        $viewUrl =
            "{$baseUrl}/api/documents/mission-receipts/{$file->path}/view";

        $downloadUrl =
            "{$baseUrl}/api/documents/mission-receipts/{$file->path}/download";


        /*
        |--------------------------------------------------------------------------
        | URL VERSION SIGNÉE
        |--------------------------------------------------------------------------
        */

        $signedViewUrl = null;
        $signedDownloadUrl = null;

        if (!empty($file->signed_path)) {

            $signedViewUrl =
                "{$baseUrl}/api/documents/mission-receipts/{$file->signed_path}/view";

            $signedDownloadUrl =
                "{$baseUrl}/api/documents/mission-receipts/{$file->signed_path}/download";
        }

        $fileData = [
            'id' => $file->id,
            'name' => $file->name,

            /*
            |--------------------------------------------------------------------------
            | ORIGINAL
            |--------------------------------------------------------------------------
            */

            'path' => $file->path,
            'view_url' => $viewUrl,
            'download_url' => $downloadUrl,

            /*
            |--------------------------------------------------------------------------
            | SIGNED COPY
            |--------------------------------------------------------------------------
            */

            'signed_path' => $file->signed_path,
            'signed_view_url' => $signedViewUrl,
            'signed_download_url' => $signedDownloadUrl,
            'signed_at' => $file->signed_at,
            'is_signed' => !empty($file->signed_path),
        ];
    }

    $items = $receipt->expenses->map(function ($expense) {
        return [
            'id' => $expense->id,
            'expense_category_id' => $expense->expense_category_id,
            'expense_category' => $expense->expense_category,
            'amount' => $expense->amount,
            'quantity' => $expense->quantity,
            'total' => $expense->total,
            'type' => $expense->type,
            'description' => $expense->description,
            'comment' => $expense->comment,
            'pivot' => [
                'allocated_amount' => (int) $expense->pivot->allocated_amount,
            ],
        ];
    })->values();

    return [
        'id' => $receipt->id,
        'code' => $receipt->code,
        'name' => $receipt->name,
        'reference' => $receipt->reference,

        'receipt_date' => $receipt->receipt_date
            ? $receipt->receipt_date->format('Y-m-d')
            : null,

        'description' => $receipt->description,

        'file' => $fileData,

        'items' => $items,

        'allocated_amount' => (int) $items->sum(function ($item) {
            return $item['pivot']['allocated_amount'];
        }),

        'created_at' => $receipt->created_at,
        'updated_at' => $receipt->updated_at,
    ];
}
    private function OldformatReceipt(MissionReceipt $receipt): array
    {
        $receipt->load([
            'file',
            'expenses.expense_category',
        ]);

        $file = $receipt->file;

        $items = $receipt->expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'expense_category_id' => $expense->expense_category_id,
                'expense_category' => $expense->expense_category,
                'amount' => $expense->amount,
                'quantity' => $expense->quantity,
                'total' => $expense->total,
                'type' => $expense->type,
                'description' => $expense->description,
                'comment' => $expense->comment,
                'pivot' => [
                    'allocated_amount' => (int)$expense->pivot->allocated_amount,
                ],
            ];
        })->values();

        return [
            'id' => $receipt->id,
            'code' => $receipt->code,
            'name' => $receipt->name,
            'reference' => $receipt->reference,
            'receipt_date' => $receipt->receipt_date
                ? $receipt->receipt_date->format('Y-m-d')
                : null,
            'description' => $receipt->description,

            'file' => $file ? [
                'id' => $file->id,
                'name' => $file->name,
                'path' => $file->path,
                'size' => $file->size,
                'type' => $file->type,
                'view_url' => url(
                    '/api/documents/mission-receipts/'
                    . $file->path
                    . '/view'
                ),
                'download_url' => url(
                    '/api/documents/mission-receipts/'
                    . $file->path
                    . '/download'
                ),
            ] : null,

            'items' => $items,

            'allocated_amount' => (int) $items->sum(function ($item) {
                return (int) $item['pivot']['allocated_amount'];
            }),

            'created_at' => $receipt->created_at,
            'updated_at' => $receipt->updated_at,
        ];
    }

    /**
     * GET /documents/{document}/mission-receipts
     *
     * Liste les justificatifs d'une mission.
     */
    public function index($document)
    {
        $mission = $this->getMission($document);

        $receipts = MissionReceipt::where('mission_id', $mission->id)
            ->with([
                'file',
                'expenses.expense_category',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $receipts->map(function ($receipt) {
                return $this->formatReceipt($receipt);
            })->values(),
        ]);
    }

    /**
     * POST /documents/{document}/mission-receipts
     *
     * Ajoute un justificatif à une mission.
     */
    public function store(Request $request, $document)
    {
        $mission = $this->getMission($document);

        $validated = $request->validate([
            'receipt' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:20480',
            ],
            'reference' => 'nullable|string|max:255',
            'receipt_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $receipt = DB::transaction(function () use (
            $request,
            $mission,
            $validated
        ) {
            $uploadedFile = $request->file('receipt');

            $receipt = MissionReceipt::create([
                'code' => (string) Str::uuid(),
                'mission_id' => $mission->id,
                'name' => $uploadedFile->getClientOriginalName(),
                'reference' => $validated['reference'] ?? null,
                'receipt_date' => $validated['receipt_date'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            $this->fileManager->replace(
                $receipt,
                'RECEIPT',
                $uploadedFile
            );

            return $receipt;
        });

        return response()->json([
            'success' => true,
            'message' => 'Justificatif ajouté avec succès.',
            'data' => $this->formatReceipt($receipt),
        ], 201);
    }

    /**
     * PUT /documents/{document}/mission-receipts/{receiptCode}/items
     *
     * Synchronise les dépenses couvertes par un justificatif.
     */
    public function syncItems(
        Request $request,
        $document,
        $receiptCode
    ) {
        $mission = $this->getMission($document);

        $receipt = $this->getReceipt(
            $mission,
            $receiptCode
        );

        $validated = $request->validate([
            'items' => 'present|array',
            'items.*.item_id' => 'required|integer|distinct',
            'items.*.allocated_amount' => 'required|numeric|min:0',
        ]);

        $items = collect($validated['items']);

        $itemIds = $items->pluck('item_id')->all();

        /*
         * Vérification de l'appartenance des dépenses
         * à la mission du justificatif.
         */
        $expenses = MissionExpense::where('mission_id', $mission->id)
            ->whereIn('id', $itemIds)
            ->get();

        if ($expenses->count() !== count($itemIds)) {
            throw ValidationException::withMessages([
                'items' => [
                    'Certaines dépenses sont introuvables ou '
                    . 'n’appartiennent pas à cette mission.',
                ],
            ]);
        }

        $syncData = [];

        foreach ($items as $item) {
            $syncData[$item['item_id']] = [
                'allocated_amount' => (int)$item['allocated_amount'],
            ];
        }

        // throw new Exception(json_encode($syncData), 1);
        

        DB::transaction(function () use ($receipt, $syncData) {
            $receipt->expenses()->sync($syncData);
        });

        $receipt->refresh();

        // throw new Exception(json_encode($receipt), 1);



        return response()->json([
            'success' => true,
            'message' => 'Associations mises à jour avec succès.',
            'data' => $this->formatReceipt($receipt),
        ]);
    }

    /**
     * DELETE /documents/{document}/mission-receipts/{receiptCode}
     *
     * Supprime le justificatif et son fichier.
     */
    public function destroy($document, $receiptCode)
{
    $mission = $this->getMission($document);

    $receipt = $this->getReceipt(
        $mission,
        $receiptCode
    );

    $file = $receipt->file;

    DB::transaction(function () use ($receipt) {
        $receipt->expenses()->detach();

        $receipt->delete();
    });

    if ($file) {
        $this->fileManager->delete($file);
    }

    return response()->json([
        'success' => true,
        'message' => 'Justificatif supprimé avec succès.',
    ]);
}
}