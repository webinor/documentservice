<?php

namespace App\Services\Mission;


use App\Models\Mission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class MissionDocumentService
{
    public function generateAll(Mission $mission)
    {
        $this->generateMissionSheet($mission);

        $this->generateMissionOrder($mission);

        $this->generateRegularizationSheet($mission);
    }

     /**
     * Générer Lettre de Mission
     */
    public function generateMissionSheet(Mission $mission)
    {
        $pdf = Pdf::loadView('templates.mission.mission-sheet',compact('mission'));

        $filename = 'mission-sheet-' . $mission->id . '.pdf';

            $path = 'missions/' . $filename;

    Storage::disk('public')->put(
        $path,
        $pdf->output()
    );

         return [
        'path' => storage_path('app/public/' . $path),
        'filename' => $filename,
        'document_id' => $mission->document->id,
        'mission_id' => $mission->id,
        'mime' => 'application/pdf',
        'size' => Storage::disk('public')->size($path),
    ];
    }

    /**
     * Générer Ordre de Mission
     */
    public function generateMissionOrder(Mission $mission)
    {
        $pdf = Pdf::loadView('templates.mission.mission-order',compact('mission'));

        $filename = 'mission-order-' . $mission->id . '.pdf';

            $path = 'missions/' . $filename;

    Storage::disk('public')->put(
        $path,
        $pdf->output()
    );

         return [
        'path' => storage_path('app/public/' . $path),
        'filename' => $filename,
        'document_id' => $mission->document->id,
        'mission_id' => $mission->id,
        'mime' => 'application/pdf',
        'size' => Storage::disk('public')->size($path),
    ];
    }

    /**
     * Générer Fiche à Régulariser
     */
    public function generateRegularizationSheet(Mission $mission)
    {
        $pdf = Pdf::loadView('templates.mission.regularization-sheet',compact('mission'));

        $filename = 'regularization-sheet-' . $mission->id . '.pdf';

            $path = 'missions/' . $filename;

    Storage::disk('public')->put(
        $path,
        $pdf->output()
    );

         return [
        'path' => storage_path('app/public/' . $path),
        'filename' => $filename,
        'document_id' => $mission->document->id,
        'mission_id' => $mission->id,
        'mime' => 'application/pdf',
        'size' => Storage::disk('public')->size($path),
    ];
    }
}