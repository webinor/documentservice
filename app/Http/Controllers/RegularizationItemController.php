<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegularizationItemRequest;
use App\Http\Requests\UpdateRegularizationItemRequest;
use App\Models\Misc\Document;
use App\Models\RegularizationItem;
use App\Services\Common\FileManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    $sheet = $document->regularization_sheet;

    // Vérifier si c'est le premier élément
    $isFirstItem = !RegularizationItem::where(
        'regularization_sheet_id',
        $sheet->id
    )->exists();


    $item = RegularizationItem::create([
        'regularization_sheet_id' => $sheet->id,

        'designation' => $isFirstItem
            ? $sheet->reason
            : ($validated['designation'] ?? null),

        // 'quantity' => $validated['quantity'] ?? 1,

        // 'unit_price' => $validated['unit_price'] ?? 0,

        // 'total_amount' =>
        //     ($validated['quantity'] ?? 1)
        //     *
        //     ($validated['unit_price'] ?? 0),

        // 'comment' => $validated['comment'] ?? null,
    ]);


    return response()->json([
        'message' => 'Ligne ajoutée avec succès.',
        'data' => $item,
    ], 201);
}



// public function getRegularizationItems( $documentIdentifier)
// {

//  if (Str::isUuid($documentIdentifier)) {
//     $document = Document::where('uuid', $documentIdentifier)->firstOrFail();
// } else {
//     $document = Document::findOrFail($documentIdentifier);
// }

  

   
//    $document->load('regularization_sheet.items.receipt');

//     if (!$document->regularization_sheet) {
    
//     return [];
//    }

//    $baseUrl = rtrim(config('app.url'), '/');
// // return $document->regularization_sheet->items;
//     $items = $document->regularization_sheet->items->map(function ($item) use ($baseUrl) {

//         return [

//             'id' => $item->id,

//             // 'app_url' => config('app.url'),
//             // 'url' => url('/'),


//             'designation' => $item->designation,

//             'quantity' => $item->quantity,

//             'unit_price' => $item->unit_price,

//             'total' => (float) $item->quantity * (float) $item->unit_price,

//             'receipt' => $item->receipt,

//             'receipt_url' => $item->receipt
//                 ? Storage::url($item->receipt->path)
//                 : null,


//                 'view_url' => $item->receipt
//     ? "{$baseUrl}/api/documents/regularization-items/view/{$item->receipt->path}"
//     : null,

// 'download_url' => $item->receipt
//     ? "{$baseUrl}/api/documents/regularization-items/download/{$item->receipt->path}"
//     : null,
// // 'view_url' => $item->receipt
// //     ? "{$baseUrl}/regularization-items/{$item->receipt->path}/view"
// //     : null,

// // 'download_url' => $item->receipt
// //     ? "{$baseUrl}/regularization-items/{$item->receipt->path}/download"
// //     : null,

//         //     'view_url' =>$item->receipt ? route(
//         //     'regularization-items.view',
//         //     $item->receipt->path 
//         // ) : null,

//         // 'download_url' => $item->receipt ? route(
//         //     'regularization-items.download',
//         //     $item->receipt->path 
//         // ) : null,

//             'created_at' => $item->created_at,
//             'updated_at' => $item->updated_at,
//         ];
//     });

//     return response()->json([
//         'success' => true,
//         'items' => $items,
//     ]);
// }

public function getRegularizationItems($documentIdentifier)
{
    $document = Str::isUuid($documentIdentifier)
        ? Document::where('uuid', $documentIdentifier)->firstOrFail()
        : Document::findOrFail($documentIdentifier);

    $document->load('regularization_sheet.items.receipt');

    if (! $document->regularization_sheet) {
        return response()->json([
            'success' => true,
            'items' => [],
        ]);
    }

    $baseUrl = rtrim(config('app.url'), '/');

    $items = $document->regularization_sheet->items->map(function ($item) use ($baseUrl) {
        return [
            'id' => $item->id,

            'designation' => $item->designation,

            // Prévisionnel
            'planned_quantity' => $item->planned_quantity,
            'planned_amount'   => $item->planned_amount,
            'planned_total'    => $item->planned_quantity * $item->planned_amount,

            // Réel
            'actual_quantity'  => $item->actual_quantity,
            'actual_amount'    => $item->actual_amount,
            'actual_total'     =>  $item->actual_quantity * $item->actual_amount,

            // Justificatif
            'receipt' => $item->receipt,

            'receipt_url' => $item->receipt
                ? Storage::url($item->receipt->path)
                : null,

            'view_url' => $item->receipt
                ? "{$baseUrl}/api/documents/regularization-items/view/{$item->receipt->path}"
                : null,

            'download_url' => $item->receipt
                ? "{$baseUrl}/api/documents/regularization-items/download/{$item->receipt->path}"
                : null,

            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    });

    $summary = [
    'planned_quantity' => $items->sum('planned_quantity'),
    'planned_total'    => $items->sum('planned_total'),
    'actual_quantity'  => $items->sum('actual_quantity'),
    'actual_total'     => $items->sum('actual_total'),
];

    return response()->json([
        'success' => true,
        'summary' => $summary,
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
        $item->total_amount = ($item->actual_quantity ?? 0) * ($item->actual_amount ?? 0);


        $item->save();


        return response()->json([
            'message' => 'Ligne mise à jour avec succès.',
            'data' => $item->fresh('receipt'),
        ]);

    });
}



public function view($path)
{
    // return ($path);
    // return Storage::disk('public')->exists($path);
    abort_unless(
        Storage::disk('public')->exists($path),
        404
    );

    return response()->file(
        Storage::disk('public')->path($path)
    );
}


public function download($path)
{
    abort_unless(
        Storage::disk('public')->exists($path),
        404,
        "Fichier introuvable"
    );

    return Storage::disk('public')->download($path);
}




}