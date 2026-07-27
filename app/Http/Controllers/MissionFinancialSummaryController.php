<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Services\Mission\MissionFinancialSummaryService;

class MissionFinancialSummaryController extends Controller
{
    protected MissionFinancialSummaryService $summaryService;

    public function __construct(
        MissionFinancialSummaryService $summaryService
    ) {
        $this->summaryService = $summaryService;
    }

    public function show( $documentId)
    {
        $summary = $this->summaryService
            ->build($documentId);

        return response()->json(array_merge(
        [
            'success' => true
        ],
        $summary
    ));

        
    }
}