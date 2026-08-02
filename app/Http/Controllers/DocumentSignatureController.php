<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Common\WorkflowRequirementService;
use App\Services\Document\DocumentService;
use Illuminate\Http\Request;

class DocumentSignatureController extends Controller
{
    public function missingForDocument(
    Request $request,
    DocumentService $documentService,
    WorkflowRequirementService $requirements,
    string $documentUuid
) {
    $document = $documentService->getDoc($documentUuid);

    $missing = [
        'signatures' => $requirements->getMissingSignatures(
            $document->id,
            $request->input('signature_types_required', [])
        ),
    ];

    return response()->json($missing);
}
}
