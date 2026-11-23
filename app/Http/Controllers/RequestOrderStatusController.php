<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\RequestOrder;
use App\Services\PurchaseRequestStatusService;
use App\Services\RequestOrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class RequestOrderStatusController extends Controller
{
    /**
     * Demande au Document Service de déterminer et d'appliquer
     * le statut approprié à une demande d'achat.
     */
    public function recalculate(
        Request $request,
        RequestOrder $requestOrder,
        RequestOrderStatusService $statusService
    ) {
        $validated = $request->validate([
            'document_uuid' => 'required|string',
            'workflow_instance_id' => 'nullable',
            'workflow_instance_step_status' => 'required|string',
            'workflow_action' => 'required|string',
        ]);

        // $purchaseRequest = PurchaseRequest::find($requestOrder);

        if (!$requestOrder) {
            throw new NotFoundHttpException(
                'Demande d’achat introuvable.'
            );
        }

        try {
            /*
             * Le service métier décide du statut cible.
             * Le contrôleur ne reçoit aucun champ "new_status".
             */
            $result = $statusService->updateFromWorkflow(
                $requestOrder,
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Le statut de la demande d’achat a été traité.',
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error(
                'Erreur lors du recalcul du statut d’une demande d’achat.',
                [
                    'request_order_id' => $requestOrder->id,
                    'document_uuid' => $validated['document_uuid'],
                    'exception' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Impossible de mettre à jour le statut du bon de commande.',
            ], 500);
        }
    }
}