<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportDocumentsRequest;
use App\Services\Export\DocumentExportService;

class DocumentExportController extends Controller
{
    protected DocumentExportService $documentExportService;

    public function __construct(
        DocumentExportService $documentExportService
    ) {
        $this->documentExportService = $documentExportService;
    }

    /**
     * Export Excel des documents sélectionnés par workflow-service.
     */
    public function exportExcel(
        ExportDocumentsRequest $request
    ) {
        return $this->documentExportService->export(
            $request->input('document_ids'),
            $request->input('document_type'),
            $request->input('columns'),
            $request->input('workflow_metadata', [])
        );
    }
}