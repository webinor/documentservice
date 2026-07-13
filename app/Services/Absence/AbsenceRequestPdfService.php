<?php

namespace App\Services\Absence;

use App\Models\AbsenceRequest;
use TCPDF;

class AbsenceRequestPdfService
{

    public function generate(AbsenceRequest $absence)
    {

        $pdf = new TCPDF(
            'P',
            'mm',
            'A4',
            true,
            'UTF-8',
            false
        );


        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor('RH');
        $pdf->SetTitle('Demande d\'absence');


        // Marges
        $pdf->SetMargins(
            15,
            15,
            15
        );


        $pdf->AddPage();


        $html = view(
            'pdf.absence-request',
            [
                'absence'=>$absence,
                'employee'=>$absence->employee,
            ]
        )->render();



        $pdf->writeHTML(
            $html,
            true,
            false,
            true,
            false,
            ''
        );


        return $pdf->Output(
            'demande-absence-'.$absence->reference.'.pdf',
            'S'
        );
    }

}