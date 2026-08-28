<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\DocumentSignaturePosition;
use App\Models\Misc\Document;
use App\Models\Misc\File;
use App\Services\Signature\SignaturePositionRequirementService;
use App\Services\Signature\SignaturePositionService;
use App\Services\UserServiceClient;
use Illuminate\Http\Request;

class DocumentSignaturePositionController extends Controller
{
    protected $userService;

    protected SignaturePositionRequirementService $service;
    protected SignaturePositionService $positionService;

    public function __construct(
    UserServiceClient $userService,
    SignaturePositionRequirementService $service,
    SignaturePositionService $positionService
) {
    $this->userService = $userService;
    $this->service = $service;
    $this->positionService = $positionService;
}

    


    /**
     * Récupérer les positions de signature.
     */
    public function index(
    Request $request,
    string $documentUuid,
    int $fileId
) {

    /*
    |--------------------------------------------------------------------------
    | Vérifier le fichier
    |--------------------------------------------------------------------------
    */

    $file = File::where('id', $fileId)
        ->firstOrFail();

    /*
    |--------------------------------------------------------------------------
    | Vérifier le document
    |--------------------------------------------------------------------------
    */

    $document = Document::whereUuid(
        $documentUuid
    )->firstOrFail();

    /*
    |--------------------------------------------------------------------------
    | Positions enrichies
    |--------------------------------------------------------------------------
    */

    $positions =
        $this->positionService
            ->getEnrichedFilePositions(
                $documentUuid,
                $fileId
            );

    return response()->json([
        'data' => $positions,
    ]);
}


  /**
 * Enregistrer une position de signature.
 */
public function store(
    Request $request,
    string $documentUuid,
    int $fileId
) {
    /*
    |--------------------------------------------------------------------------
    | Utilisateur signataire
    |--------------------------------------------------------------------------
    */

    $currentUser = $request->get('user');

    if (
        !$currentUser ||
        !isset($currentUser['id'])
    ) {
        return response()->json([
            'message' => 'Utilisateur authentifié introuvable.',
        ], 401);
    }

    $userId = (int) $currentUser['id'];


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([

        'signature_type' => [
            'required',
            'string',
            'max:100',
        ],

        'page_number' => [
            'required',
            'integer',
            'min:1',
        ],

        'x' => [
            'required',
            'numeric',
        ],

        'y' => [
            'required',
            'numeric',
        ],

        'width' => [
            'required',
            'numeric',
            'min:1',
        ],

        'height' => [
            'required',
            'numeric',
            'min:1',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Document
    |--------------------------------------------------------------------------
    */

    $document = Document::query()
        ->where('uuid', $documentUuid)
        ->firstOrFail();


    /*
    |--------------------------------------------------------------------------
    | Fichier
    |--------------------------------------------------------------------------
    |
    | Le fichier doit réellement appartenir au document.
    |
    | Dans le cas de la fiche de régularisation, les fichiers
    | sont attachés aux justificatifs (RegularizationReceipt)
    | et non directement au Document.
    |
    | Cette vérification doit donc être adaptée à ton modèle
    | de rattachement.
    |
    */

    $file = File::query()
        ->where('id', $fileId)
        ->firstOrFail();


    /*
    |--------------------------------------------------------------------------
    | Vérifier le numéro de page
    |--------------------------------------------------------------------------
    */

    if (
        $file->page_count !== null &&
        $validated['page_number'] > $file->page_count
    ) {
        return response()->json([
            'message' =>
                'Le numéro de page indiqué dépasse le nombre de pages du fichier.',

            'page_number' =>
                $validated['page_number'],

            'page_count' =>
                $file->page_count,
        ], 422);
    }


    /*
    |--------------------------------------------------------------------------
    | Enregistrer / mettre à jour
    |--------------------------------------------------------------------------
    |
    | Une position est unique pour :
    |
    | document
    | + fichier
    | + utilisateur
    | + type de signature
    | + page
    |
    */

    $position = DocumentSignaturePosition::updateOrCreate(

        [
            'document_id' =>
                $document->id,

            'file_id' =>
                $file->id,

            'user_id' =>
                $userId,

            'signature_type' =>
                $validated['signature_type'],

            'page_number' =>
                $validated['page_number'],
        ],

        [
            'x' =>
                $validated['x'],

            'y' =>
                $validated['y'],

            'width' =>
                $validated['width'],

            'height' =>
                $validated['height'],
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | Enrichir immédiatement la position
    |--------------------------------------------------------------------------
    */

    $position = $this->positionService->enrichPositions(
        collect([$position]),
        $document->id
    )->first();


    /*
    |--------------------------------------------------------------------------
    | Réponse
    |--------------------------------------------------------------------------
    */

    return response()->json([

        'message' =>
            'Position de signature enregistrée.',

        'data' =>
            $position,

    ], 201);
}

    public function status(
        Request $request,
        string $documentUuid
    ) {

        $userId = $request->userId;

        $result = $this->service->checkAllReceiptPagesPositioned(
            $documentUuid,
            $userId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}