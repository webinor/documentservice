<?php

namespace App\Http\Controllers;

use App\Exports\Mission\MissionFinancialReportExport;
use App\Models\Misc\Document;
use App\Models\Mission;
use App\Services\Mission\MissionFinancialReportService;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MissionFinancialReportController extends Controller
{
    public function show(
        Document $document,
        MissionFinancialReportService $service
    )
    {

    /**
         * Vérification type mission
         */
        if ($document->document_type->slug !== "mission") {
            throw new Exception("Document is not a mission.");
        }

        $mission = $document->mission;
        return response()->json(
            $service->generate($mission)
        );
    }


    public function export(Document $document, 
        MissionFinancialReportService $financialReportService)
{
       if ($document->document_type->slug !== "mission") {
            throw new Exception("Document is not a mission.");
        }

        $mission = $document->mission;

    $report = $financialReportService->generate($mission);

    return Excel::download(new MissionFinancialReportExport($report),
        'mission_'.$mission->id.'_report.xlsx'
    );
}
}
