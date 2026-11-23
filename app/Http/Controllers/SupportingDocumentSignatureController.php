<?php

namespace App\Http\Controllers;

use App\Services\Signature\SupportingDocumentSignatureService;
use Exception;
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
    ): JsonResponse
     {

        try {

    
        
        //   return response()->json(
        //         $request->get('signatureType' , "RECEIPT")
        //     );
        // throw new Exception(json_encode($request->get('signatureType' , "RECEIPT")), 1);
        
        // return $request->input("signatureType","RECEIPT");
        $signatureType = $request->get('signatureType' , "RECEIPT");

            $result =
                $this->service->apply(
                    $documentUuid,
                    $signatureType
                    // (floatval($scale))
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