<?php

namespace App\Http\Controllers;

use App\Models\Misc\Attachment;
use App\Models\Misc\AttachmentType;
use App\Models\Misc\Document;
use App\Models\Misc\File;
use App\Models\Mission;
use App\Services\Mission\MissionDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MissionDocumentController extends Controller
{

    protected MissionDocumentService $missionDocumentService;

    public function __construct(MissionDocumentService $missionDocumentService) {
        $this->missionDocumentService = $missionDocumentService;
    }

    private function handleMissionDocument(
    Document $document,
    array $user_connected,
    string $attachment_slug,
    ?\Illuminate\Http\UploadedFile $uploadedFile = null,
    ?array $generatedFile = null
) {

    /**
     * ---------------------------------------------------------
     * Cas 1 : fichier uploadé depuis request
     * ---------------------------------------------------------
     */

    if ($uploadedFile) {

        $fileName = Str::random(20) . "_" . time() . "." .
            $uploadedFile->extension();

        $type = $uploadedFile->getClientMimeType();

        $size = $uploadedFile->getSize();

        $uploadedFile->move(
            storage_path("app/public/documents_attachments"),
            $fileName
        );
    }

    /**
     * ---------------------------------------------------------
     * Cas 2 : fichier généré automatiquement
     * ---------------------------------------------------------
     */

    elseif ($generatedFile) {

        $extension = pathinfo(
            $generatedFile['filename'],
            PATHINFO_EXTENSION
        );

        $fileName = Str::random(20) . "_" . time() . "." . $extension;

        $destination = storage_path(
            "app/public/documents_attachments/" . $fileName
        );

        copy(
            $generatedFile['path'],
            $destination
        );

        $type = $generatedFile['mime'];

        $size = $generatedFile['size'];
    }

    else {

        throw new \Exception("Aucun fichier fourni.");
    }

    /**
     * ---------------------------------------------------------
     * Création attachment
     * ---------------------------------------------------------
     */

    $attachment = new Attachment();

    $attachment->document_id = $document->id;
    $attachment->is_main = false;
    $attachment->source = "UPLOAD";
    $attachment->created_by = $user_connected["id"];

    $attachment->attachment_type_id = AttachmentType::whereSlug(
    $attachment_slug
    )->first()->id;

    $attachment->save();

    /**
     * ---------------------------------------------------------
     * Création file
     * ---------------------------------------------------------
     */

    $fileModel = new File();

    $fileModel->path = $fileName;
    $fileModel->type = $type;
    $fileModel->size = $size;

    $attachment->file()->save($fileModel);

    // GeneratePdfThumbnail::dispatch($attachment);

    return $attachment;
}

      public function generate(Request $request )
    {

    //   return
        $document_id = $request->get("document_id",0);
        $document = Document::with('mission')->find($document_id);
        $user_connected = $request->get("user"); // récupéré du user-service

            //  app(MissionDocumentService::class)
            // ->generateAll($document->mission);


        //      return response()->json([
        //     "document" => $document,
            
        // ]);

        // 🔥 génération documents
        $missionLetter = $this->missionDocumentService->generateMissionLetter($document->mission);
        $missionOrder = $this->missionDocumentService->generateMissionOrder($document->mission);
        $missionSheet = $this->missionDocumentService->generateRegularizationSheet($document->mission);

$letter_attachment = $this->handleMissionDocument(
    $document,
    $user_connected,
    'lettre-de-mission',
    null,
    $missionLetter
);

$order_attachment = $this->handleMissionDocument(
    $document,
    $user_connected,
    'ordre-de-mission',
    null,
    $missionOrder
);

$letter_attachment = $this->handleMissionDocument(
    $document,
    $user_connected,
    'feuille-de-mission',
    null,
    $missionSheet
);

         return response()->json([
            "success" => true,
            'message' => 'Documents générés avec succès',
            "documents" => [
                "letter" => $missionLetter,
                "order" => $missionOrder,
                "sheet" => $missionSheet,
            ]
        ]);

        return response()->json([
        'message' => 'Documents générés avec succès',
        'document_id' => $document_id,
        'document' => $document,
    ]);

     

    }
    /**
     * Générer Lettre de Mission
     */
    public function generateMissionLetter(Mission $mission)
    {
        $pdf = Pdf::loadView(
            'pdf.mission-letter',
            compact('mission')
        );

        $filename = 'mission-letter-' . $mission->id . '.pdf';

        Storage::put(
            'missions/' . $filename,
            $pdf->output()
        );

        return response()->json([
            'success' => true,
            'url' => asset('storage/missions/' . $filename),
        ]);
    }

    /**
     * Générer Ordre de Mission
     */
    public function generateMissionOrder(Mission $mission)
    {
        $pdf = Pdf::loadView(
            'pdf.mission-order',
            compact('mission')
        );

        $filename = 'mission-order-' . $mission->id . '.pdf';

        Storage::put(
            'missions/' . $filename,
            $pdf->output()
        );

        return response()->json([
            'success' => true,
            'url' => asset('storage/missions/' . $filename),
        ]);
    }

    /**
     * Générer Fiche à Régulariser
     */
    public function generateRegularizationSheet(Mission $mission)
    {
        $pdf = Pdf::loadView(
            'pdf.regularization-sheet',
            compact('mission')
        );

        $filename = 'regularization-sheet-' . $mission->id . '.pdf';

        Storage::put(
            'missions/' . $filename,
            $pdf->output()
        );

        return response()->json([
            'success' => true,
            'url' => asset('storage/missions/' . $filename),
        ]);
    }
}