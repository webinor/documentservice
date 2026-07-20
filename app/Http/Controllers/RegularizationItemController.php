<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegularizationItemRequest;
use App\Http\Requests\UpdateRegularizationItemRequest;
use App\Models\Misc\Document;
use App\Models\RegularizationItem;
use App\Services\Common\FileManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RegularizationItemController extends Controller
{
    /**
     * Ajouter une ligne à une fiche à régulariser.
     */
    public function store(
        StoreRegularizationItemRequest $request,
        Document $document
    ) {
        $document->load('regularization_sheet');

        abort_if(
            !$document->regularization_sheet,
            404,
            "Aucune fiche à régulariser associée à ce document."
        );

        $validated = $request->validated();

        $item = RegularizationItem::create([
            'regularization_sheet_id' => $document->regularization_sheet->id,

            // 'designation' => $validated['designation'] ?? '',

            // 'quantity' => $validated['quantity'] ?? 1,

            // 'unit_price' => $validated['unit_price'] ?? 0,

            // 'total_amount' =>
            //     ($validated['quantity'] ?? 1)
            //     * ($validated['unit_price'] ?? 0),

            // 'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Ligne ajoutée avec succès.',
            'data' => $item,
        ], 201);
    }



public function getRegularizationItems(Document $document)
{
    $document->load('regularization_sheet.items.receipt');

    $items = $document->regularization_sheet->items->map(function ($item) {

        return [
            'id' => $item->id,
            'designation' => $item->designation,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'total' => (float) $item->quantity * (float) $item->unit_price,

            'receipt' => $item->receipt,

            'receipt_url' => $item->receipt
                ? Storage::url($item->receipt->path)
                : null,

            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    });

    return response()->json([
        'success' => true,
        'items' => $items,
    ]);
}

    /**
     * Supprimer une ligne d'une fiche à régulariser.
     */
    public function deleteItem(
        Document $document,
        RegularizationItem $item
    ) {

    // return $item;

        $document->load('regularization_sheet');

        abort_if(
            !$document->regularization_sheet,
            404,
            "Aucune fiche à régulariser associée à ce document."
        );

        // Vérifie que la ligne appartient bien à cette fiche
        abort_if(
            $item->regularization_sheet_id !== $document->regularization_sheet->id,
            403,
            "Cette ligne n'appartient pas à cette fiche."
        );

        // Si un justificatif est stocké, on peut également le supprimer ici.
        // if ($item->receipt && Storage::disk('public')->exists($item->receipt)) {
        //     Storage::disk('public')->delete($item->receipt);
        // }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ligne supprimée avec succès.',
        ]);
    }




public function updateItem(
    UpdateRegularizationItemRequest $request,
    Document $document,
    RegularizationItem $item,
    FileManager $fileManager
) {
    $document->load('regularization_sheet');

    abort_if(
        !$document->regularization_sheet,
        404,
        "Aucune fiche à régulariser associée à ce document."
    );

    abort_if(
        $item->regularization_sheet_id !== $document->regularization_sheet->id,
        403,
        "Cette ligne n'appartient pas à cette fiche."
    );

    $validated = collect($request->validated())
    ->except('receipt')
    ->toArray();


    return DB::transaction(function () use (
        $request,
        $item,
        $validated,
        $fileManager
    ) {

        /**
         * Upload du justificatif
         */
         if ($request->hasFile('receipt')) {

            $fileManager->replace(
                $item,
                'RECEIPT',
                $request->file('receipt')
            );

        }


        /**
         * Mise à jour ligne
         */
        $item->fill($validated);


        /**
         * Calcul automatique
         */
        $item->total_amount =
            ($item->quantity ?? 0)
            *
            ($item->unit_price ?? 0);


        $item->save();


        return response()->json([
            'message' => 'Ligne mise à jour avec succès.',
            'data' => $item->fresh('receipt'),
        ]);

    });
}
}