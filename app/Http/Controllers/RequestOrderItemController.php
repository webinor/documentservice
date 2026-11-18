<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestOrderItemRequest;
use App\Http\Requests\UpdateRequestOrderItemRequest;
use App\Models\RequestOrder;
use App\Models\RequestOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RequestOrderItemController extends Controller
{
    /**
     * Liste les articles d'un bon de commande.
     *
     * @param RequestOrder $requestOrder
     * @return JsonResponse
     */

    /**
 * Liste les articles d'un bon de commande
 * ainsi que ses échéances de paiement.
 *
 * @param RequestOrder $requestOrder
 * @return JsonResponse
 */
public function index(RequestOrder $requestOrder)
{
    $requestOrder->load(['initialPaymentSchedule', 'paymentSchedules']);

    $items = $requestOrder->items()->get();

    return response()->json([
        'success' => true,
        'data' => $items,
        'request_order' => $requestOrder,
    ]);
}

    public function Oldindex(RequestOrder $requestOrder)
    {
        $items = $requestOrder->items()->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'request_order' => $requestOrder
        ]);
    }

    /**
     * Enregistre un nouvel article dans un bon de commande.
     *
     * @param StoreRequestOrderItemRequest $request
     * @param RequestOrder $requestOrder
     * @return JsonResponse
     */
    public function store(
    StoreRequestOrderItemRequest $request,
    RequestOrder $requestOrder
) {

// return $requestOrder;
    $validated = $request->validated();

    $quantity = (int) ($validated['quantity'] ?? 1);
    $unitPrice = (int) ($validated['unit_price'] ?? 0);

    $item = $requestOrder->items()->create([
        'designation' => $validated['designation'] ?? '',
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'total' => $quantity * $unitPrice,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Ligne ajoutée au bon de commande.',
        'data' => $item,
    ], 201);
}

    /**
     * Affiche un article du bon de commande.
     *
     * @param RequestOrder $requestOrder
     * @param RequestOrderItem $requestOrderItem
     * @return JsonResponse
     */
    public function show(
        RequestOrder $requestOrder,
        RequestOrderItem $requestOrderItem
    ) {
        $item = $requestOrder->items()
            ->findOrFail($requestOrderItem->id);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /**
 * Met à jour un article du bon de commande.
 *
 * @param UpdateRequestOrderItemRequest $request
 * @param RequestOrder $requestOrder
 * @param RequestOrderItem $item
 * @return JsonResponse
 */
public function update(
    UpdateRequestOrderItemRequest $request,
    RequestOrder $requestOrder,
    RequestOrderItem $item
) {
    $validated = $request->validated();

    DB::transaction(function () use (
        $item,
        $validated,
        $requestOrder
    ) {
        $quantity = array_key_exists('quantity', $validated)
            ? (float) $validated['quantity']
            : (float) $item->quantity;

        $unitPrice = array_key_exists('unit_price', $validated)
            ? (float) $validated['unit_price']
            : (float) $item->unit_price;

        $receivedQuantity = array_key_exists('received_quantity', $validated)
            ? (float) $validated['received_quantity']
            : (float) $item->received_quantity;

        $actualUnitPrice = array_key_exists('actual_unit_price', $validated)
            ? (float) $validated['actual_unit_price']
            : (float) $item->actual_unit_price;

        $item->fill($validated);

        $item->quantity = $quantity;
        $item->unit_price = $unitPrice;
        $item->received_quantity = $receivedQuantity;
        $item->actual_unit_price = $actualUnitPrice;

        // Montant total de l'article commandé.
        $item->total_amount = $quantity * $unitPrice;

        // Montant total réellement réceptionné.
        $item->total_received_amount = $receivedQuantity * $actualUnitPrice;

        $item->save();

        if (
            array_key_exists('quantity', $validated) ||
            array_key_exists('unit_price', $validated)
        ) {
            // Recalcul du montant total du bon de commande.
            $requestOrder->total_amount = $requestOrder->items()
                ->sum('total_amount');

            $requestOrder->save();
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'Article modifié avec succès.',
        'data' => $item->fresh(),
    ]);
}
    /**
     * Met à jour un article du bon de commande.
     *
     * @param UpdateRequestOrderItemRequest $request
     * @param RequestOrder $requestOrder
     * @param RequestOrderItem $requestOrderItem
     * @return JsonResponse
     */
    public function Oldupdate(
        UpdateRequestOrderItemRequest $request,
        RequestOrder $requestOrder,
        RequestOrderItem $item
    ) {
        // $item = $requestOrder->items()
        //     ->findOrFail($item->id);

        // return $item;
        $validated = $request->validated();

        DB::transaction(function () use ($item, $validated) {
            $quantity = array_key_exists('quantity', $validated)
                ? (int) $validated['quantity']
                : (int) $item->quantity;

            $unitPrice = array_key_exists('unit_price', $validated)
                ? (int) $validated['unit_price']
                : (int) $item->unit_price;

            $item->fill($validated);

            $item->quantity = $quantity;
            $item->unit_price = $unitPrice;
            // $item->total = $quantity * $unitPrice;

            $item->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Article modifié avec succès.',
            'data' => $item->fresh(),
        ]);
    }

    /**
     * Supprime un article du bon de commande.
     *
     * @param RequestOrder $requestOrder
     * @param RequestOrderItem $requestOrderItem
     * @return JsonResponse
     */
    public function destroy(
        RequestOrder $requestOrder,
        RequestOrderItem $item
    ) {
        

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé avec succès.',
        ]);
    }
}