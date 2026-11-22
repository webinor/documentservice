<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Services\Financial\DocumentFinancialSummaryService;
use App\Services\Mission\MissionFinancialSummaryService;

class MissionFinancialSummaryController extends Controller
{
    protected DocumentFinancialSummaryService $document_financial_summary_service;

    public function __construct(
        DocumentFinancialSummaryService $document_financial_summary_service
    ) {
        $this->document_financial_summary_service = $document_financial_summary_service;
    }

    public function show( $documentId)
    {
        $summary = $this->document_financial_summary_service
            ->build($documentId);

        return response()->json(array_merge(
        [
            'success' => true
        ],
        $summary
    ));

        
    }
}