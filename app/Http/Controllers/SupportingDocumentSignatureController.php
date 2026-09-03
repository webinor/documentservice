<?php

namespace App\Http\Controllers;

use App\Services\Signature\SupportingDocumentSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SupportingDocumentSignatureController
    extends Controller
{
    protected SupportingDocumentSignatureService $service;

    public function __construct(
        SupportingDocumentSignatureService $service
    ) {
        $this->service = $service;
    }

    /**
     * Apposer les signatures sur les pièces justificatives.
     */
    public function apply(
        Request $request,
        string $documentUuid
    ): JsonResponse {

        try {

        $scale = $request->get('scale' , 1.0);

            $result =
                $this->service->apply(
                    $documentUuid,
                    (floatval($scale))
                );

            return response()->json(
                $result
            );

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,

                'message' =>
                    'Erreur lors de l’apposition des signatures.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }
}