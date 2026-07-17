<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegularizationItemRequest;
use App\Http\Requests\UpdateRegularizationItemRequest;
use App\Models\Misc\Document;
use App\Models\RegularizationItem;
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


        public function getRegularizationItems(
        Document $document
        // MissionExpenseCalculatorService $service
        // MissionExpenseService $service
    ) {
        // $document->load('mission.mission_expenses.expense_category');
        $document->load("regularization_sheet.items");

        $items = $document->regularization_sheet->items;

        return response()->json(
        
                [
                    "success" => true,
                    "items" => $items
                ],
              
            
        );
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



    /**
     * Mettre à jour une ligne de fiche à régulariser.
     */
    public function updateItem(
        UpdateRegularizationItemRequest $request,
        Document $document,
        RegularizationItem $item
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

        $validated = $request->validated();

        /**
         * Upload du justificatif
         */
        if ($request->hasFile('receipt')) {

            // Suppression de l'ancien fichier
            if (
                $item->receipt &&
                Storage::disk('public')->exists($item->receipt)
            ) {
                Storage::disk('public')->delete($item->receipt);
            }

            $validated['receipt'] = $request
                ->file('receipt')
                ->store(
                    'regularization-items',
                    'public'
                );
        }

        /**
         * Mise à jour
         */
        $item->fill($validated);

        // /**
        //  * Calcul automatique du total
        //  */
        // $item->total_amount =
        //     ($item->quantity ?? 0)
        //     * ($item->unit_price ?? 0);

        $item->save();

        return response()->json([
            'message' => 'Ligne mise à jour avec succès.',
            'data' => $item->fresh(),
        ]);
    }
}