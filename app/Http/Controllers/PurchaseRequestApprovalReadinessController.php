<?php

namespace App\Http\Controllers;

use App\Services\PurchaseRequestApprovalReadinessService;
use Illuminate\Http\JsonResponse;

class PurchaseRequestApprovalReadinessController extends Controller
{
    /**
     * @var PurchaseRequestApprovalReadinessService
     */
    private $readinessService;

    /**
     * Injection du service de vérification.
     *
     * @param PurchaseRequestApprovalReadinessService $readinessService
     */
    public function __construct(
        PurchaseRequestApprovalReadinessService $readinessService
    ) {
        $this->readinessService = $readinessService;
    }

    /**
     * Vérifie si les articles d'une demande d'achat
     * sont correctement renseignés pour validation.
     *
     * GET /documents/{documentId}/purchase-request-approval-readiness
     *
     * @param int $documentId
     * @return JsonResponse
     */
    public function show(int $documentId): JsonResponse
    {
        $summary = $this->readinessService->evaluateByDocumentId(
            $documentId
        );

        if ($summary === null) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune demande d\'achat trouvée pour ce document.',
                'purchase_request_summary' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vérification de la demande d\'achat effectuée.',
            'purchase_request_summary' => $summary,
        ], 200);
    }
}