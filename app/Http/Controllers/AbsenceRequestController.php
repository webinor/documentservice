<?php

namespace App\Http\Controllers;



use App\Models\Misc\Document;
use App\Services\Absence\AbsenceRequestPdfService;

class AbsenceRequestController extends Controller
{

        /**
     * Génération PDF de la demande d'absence
     */
    public function pdf(
        Document $document,
        AbsenceRequestPdfService $pdfService
    )
    {

    $leaveRequest = $document->absence_request;

        // Charger les relations nécessaires
        $leaveRequest->load([
            'employee',
            'leaveType',
            'employee.department',
            'employee.position'
        ]);


        $pdf = $pdfService->generate(
            $leaveRequest
        );


        return response($pdf)
            ->header(
                'Content-Type',
                'application/pdf'
            );
    }
    

}
