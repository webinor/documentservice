<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class PurchaseRequestStatusController extends Controller
{
    /**
     * Demande au Document Service de déterminer et d'appliquer
     * le statut approprié à une demande d'achat.
     */
    public function recalculate(
        Request $request,
        int $purchaseRequestId,
        PurchaseRequestStatusService $statusService
    ) {
        $validated = $request->validate([
            'document_uuid' => 'required|string',
            'workflow_instance_id' => 'nullable',
            'workflow_instance_step_status' => 'required|string',
            'workflow_action' => 'required|string',
        ]);

        $purchaseRequest = PurchaseRequest::find($purchaseRequestId);

        if (!$purchaseRequest) {
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
                $purchaseRequest,
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
                    'purchase_request_id' => $purchaseRequestId,
                    'document_uuid' => $validated['document_uuid'],
                    'exception' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Impossible de mettre à jour le statut de la demande d’achat.',
            ], 500);
        }
    }
}