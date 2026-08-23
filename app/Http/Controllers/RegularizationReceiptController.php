<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegularizationReceiptRequest;
use App\Http\Requests\UpdateRegularizationReceiptRequest;
use App\Models\Misc\Document;
use App\Models\RegularizationReceipt;
use App\Services\Common\FileManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

 use App\Http\Requests\SyncRegularizationReceiptItemsRequest;
use App\Services\Document\DocumentService;
use Illuminate\Support\Facades\Storage;

class RegularizationReceiptController extends Controller
{

 private DocumentService $documentService;

  

    public function __construct(
        DocumentService $documentService
    ) {
        $this->documentService = $documentService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function getRegularizationReceipts($documentIdentifier)
{
   
$document = $this->documentService->getDoc($documentIdentifier);

    $document->load([
        'regularization_sheet',
        'regularization_sheet.receipts.file',
        'regularization_sheet.receipts.items',
    ]);

    if (!$document->regularization_sheet) {
        return response()->json([
            'success' => true,
            'receipts' => [],
        ]);
    }

    $baseUrl = rtrim(config('app.url'), '/');

    $receipts = $document
        ->regularization_sheet
        ->receipts
        ->map(function ($receipt) use ($baseUrl) {

            return [
                'id' => $receipt->id,

                'code' => $receipt->code,

                'reference' => $receipt->reference,

                'file' => $receipt->file
                    ? [
                        'id' => $receipt->file->id,
                        'name' => $receipt->file->name,
                        'path' => $receipt->file->path,

                        'view_url' =>
                            "{$baseUrl}/api/documents/regularization-items/{$receipt->file->path}/view",

                        'download_url' =>
                            "{$baseUrl}/api/documents/regularization-items/{$receipt->file->path}/download",
                    ]
                    : null,

                'items' => $receipt->items,

                'created_at' => $receipt->created_at,
                'updated_at' => $receipt->updated_at,
            ];
        });

    return response()->json([
        'success' => true,
        'receipts' => $receipts,
    ]);
}

  

/**
     * Synchroniser les dépenses couvertes par un justificatif.
     */
    public function syncItems(
        SyncRegularizationReceiptItemsRequest $request,
        $documentIdentifier,
        $receiptCode
    ) {

$document = $this->documentService->getDoc($documentIdentifier);

$receipt = RegularizationReceipt::whereCode($receiptCode)->first();


        /*
         * =====================================================
         * 1. Vérifier que le document possède une fiche
         * =====================================================
         */

        $document->load('regularization_sheet');

        abort_if(
            !$document->regularization_sheet,
            404,
            "Aucune fiche de régularisation associée à ce document."
        );


        /*
         * =====================================================
         * 2. Vérifier que le justificatif appartient
         *    à cette fiche
         * =====================================================
         */

        abort_if(
            $receipt->regularization_sheet_id !==
            $document->regularization_sheet->id,
            403,
            "Ce justificatif n'appartient pas à cette fiche."
        );


        /*
         * =====================================================
         * 3. Récupérer les données validées
         * =====================================================
         */

        $validated = $request->validated();
// return
        $items = $validated['items'] ?? [];


        return DB::transaction(function () use (
            $items,
            $document,
            $receipt
        ) {

            /*
             * =================================================
             * 4. Récupérer les IDs des dépenses
             * =================================================
             */

            $itemIds = collect($items)
                ->pluck('item_id')
                ->unique()
                ->values();

            


            /*
             * =================================================
             * 5. Vérifier que toutes les dépenses appartiennent
             *    à la fiche de régularisation du document
             * =================================================
             */

            $validItemCount = $document
                ->regularization_sheet
                ->items()
                ->whereIn('id', $itemIds)
                ->count();


            abort_if(
                $validItemCount !== $itemIds->count(),
                422,
                "Une ou plusieurs dépenses n'appartiennent pas à cette fiche."
            );


            /*
             * =================================================
             * 6. Construire les données de la table pivot
             * =================================================
             *
             * Exemple :
             *
             * [
             *     10 => [
             *         'allocated_amount' => 80000
             *     ],
             *
             *     11 => [
             *         'allocated_amount' => 20000
             *     ]
             * ]
             *
             */

            $syncData = collect($items)
                ->mapWithKeys(function ($item) {

                    return [
                        $item['item_id'] => [
                            'allocated_amount' =>
                                (int)$item['allocated_amount'],
                        ],
                    ];

                })
                ->toArray();


            /*
             * =================================================
             * 7. Synchroniser la table pivot
             * =================================================
             *
             * Les anciennes associations qui ne sont plus
             * présentes dans $syncData seront supprimées.
             *
             * Les nouvelles seront ajoutées.
             *
             * Les allocated_amount seront mises à jour.
             */

            $receipt
                ->items()
                ->sync($syncData);


            /*
             * =================================================
             * 8. Recharger les relations
             * =================================================
             */

            $receipt->load([
                'file',
                'items',
            ]);


            /*
             * =================================================
             * 9. Retourner le justificatif mis à jour
             * =================================================
             */

            return response()->json([
                'success' => true,

                'message' =>
                    'Les dépenses couvertes par le justificatif ont été mises à jour.',

                'data' => $receipt,
            ]);
        });
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRegularizationReceiptRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(
    StoreRegularizationReceiptRequest $request,
    $documentIdentifier,
    FileManager $fileManager
) {

 $document = Str::isUuid($documentIdentifier)
        ? Document::where('uuid', $documentIdentifier)->firstOrFail()
        : Document::findOrFail($documentIdentifier);

    $document->load('regularization_sheet');

    abort_if(
        !$document->regularization_sheet,
        404,
        "Aucune fiche de régularisation associée à ce document."
    );

    return DB::transaction(function () use (
        $request,
        $document,
        $fileManager
    ) {

        /*
         * Création du justificatif
         */
        $receipt = RegularizationReceipt::create([
            'code' => Str::random(10),
            'regularization_sheet_id' => $document->regularization_sheet->id,
            'reference' => $request->input('reference'),
        ]);


        /*
         * Enregistrement du fichier
         */
        if ($request->hasFile('receipt')) {

            $fileManager->replace(
                $receipt,
                'RECEIPT',
                $request->file('receipt')
            );

        }


        /*
         * Retour
         */
        $receipt->load('file');

        return response()->json([
            'success' => true,
            'message' => 'Justificatif ajouté avec succès.',

            'data' => $receipt,

        ], 201);
    });
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RegularizationReceipt  $regularizationReceipt
     * @return \Illuminate\Http\Response
     */
    public function show(RegularizationReceipt $regularizationReceipt)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RegularizationReceipt  $regularizationReceipt
     * @return \Illuminate\Http\Response
     */
    public function edit(RegularizationReceipt $regularizationReceipt)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRegularizationReceiptRequest  $request
     * @param  \App\Models\RegularizationReceipt  $regularizationReceipt
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRegularizationReceiptRequest $request, RegularizationReceipt $regularizationReceipt)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RegularizationReceipt  $regularizationReceipt
     * @return \Illuminate\Http\Response
     */
    public function destroy(
    string $documentIdentifier,
    string $receiptCode
) {


$document = $this->documentService->getDoc($documentIdentifier);

$receipt = RegularizationReceipt::whereCode($receiptCode)->first();

    $document->load('regularization_sheet');

    abort_if(
        ! $document->regularization_sheet,
        404,
        'Aucune fiche de régularisation associée à ce document.'
    );

    abort_if(
        $receipt->regularization_sheet_id !==
        $document->regularization_sheet->id,
        403,
        'Ce justificatif n’appartient pas à cette fiche.'
    );

    DB::transaction(function () use ($receipt) {

        // Supprime les associations avec les dépenses
        $receipt->items()->detach();

        // Si tu veux également supprimer le fichier physique
        if ($receipt->file) {
            Storage::delete($receipt->file->path);
            $receipt->file->delete();
        }

        $receipt->delete();
    });

    return response()->json([
        'success' => true,
        'message' => 'Justificatif supprimé avec succès.',
    ]);
}
}
