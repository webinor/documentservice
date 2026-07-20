<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Services\Document\DocumentCapabilitiesService;
use App\Services\Document\DocumentService;
use App\Services\DocumentViewService;
use Illuminate\Http\Request;

class DocumentCapabilitiesController extends Controller
{
    public function show(
        Request $request,
        Document $document,
        DocumentViewService $documentViewService,
        DocumentService $documentService,
        DocumentCapabilitiesService $service
    ) {
        /**
         * Chargement des relations nécessaires
         */
        $enrichedDocument = $documentService->enrichDocument($document);

        
        $workflowContext = $documentViewService->getWorkflowStatusStatus(
        $document->id);

        /**
         * Contexte utilisateur connecté
         */
        // return
        $userInfo = request()->get("user");

        $user = [
            'id' => $userInfo['id'],
            'employee_id' =>$userInfo['employee_id'],
            'role_id' => $userInfo['role_ids'] ?? null,
        ];

        /**
         * Résolution des capacités
         */
        $capabilities = $service->resolve(
            $enrichedDocument,
            $workflowContext,
            $user
        );

        return response()->json([
            'success' => true,
            'data' => $capabilities,
        ]);
    }
}