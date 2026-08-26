<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\DocumentSignaturePosition;
use App\Models\Misc\Document;
use App\Models\Misc\File;
use App\Services\UserServiceClient;
use Illuminate\Http\Request;

class DocumentSignaturePositionController extends Controller
{
    protected $userService;

    public function __construct(
        UserServiceClient $userService
    ) {
        $this->userService = $userService;
    }

    /**
     * Enrichir une collection de positions avec :
     *
     * - informations du signataire
     * - signature
     * - état de la signature
     * - date de signature
     */
    protected function enrichPositions(
        $positions,
        int $documentId
    ) {
        /*
        |--------------------------------------------------------------------------
        | IDs des signataires
        |--------------------------------------------------------------------------
        */

        $userIds = $positions
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Récupérer les informations depuis User Service
        |--------------------------------------------------------------------------
        */

        $signatories = [];

        if (!empty($userIds)) {
            $signatories =
                $this->userService
                    ->getUsersSignatures($userIds);
        }

        $signatoriesById = collect(
            $signatories
        )->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | Récupérer les signatures effectives
        |--------------------------------------------------------------------------
        */

        $signatures = DocumentSignature::query()
            ->where('document_id', $documentId)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Indexer par signature_type + signed_by
        |--------------------------------------------------------------------------
        */

        $signaturesByUserAndType = $signatures
            ->keyBy(function ($signature) {

                return
                    $signature->signature_type
                    . '_'
                    . $signature->signed_by;
            });

        /*
        |--------------------------------------------------------------------------
        | Enrichir les positions
        |--------------------------------------------------------------------------
        */

        $positions->transform(
            function ($position) use (
                $signatoriesById,
                $signaturesByUserAndType
            ) {

                /*
                |--------------------------------------------------------------------------
                | Signataire attendu
                |--------------------------------------------------------------------------
                */

                $signatory =
                    $signatoriesById->get(
                        $position->user_id
                    );

                /*
                |--------------------------------------------------------------------------
                | Signature effective du signataire
                |--------------------------------------------------------------------------
                */

                $signatureKey =
                    $position->signature_type
                    . '_'
                    . $position->user_id;

                $signature =
                    $signaturesByUserAndType->get(
                        $signatureKey
                    );

                /*
                |--------------------------------------------------------------------------
                | Informations du signataire
                |--------------------------------------------------------------------------
                */

                $position->signatory = null;

                if ($signatory) {

                    $position->signatory = [
                        'id' =>
                            $signatory['id'],

                        'name' =>
                            $signatory['name'],

                        'signature_url' =>
                            isset(
                                $signatory['signature_url']
                            )
                                ? $signatory['signature_url']
                                : null,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | État de la signature
                |--------------------------------------------------------------------------
                */

                $position->is_signed =
                    $signature !== null
                    && $signature->signed_at !== null;

                /*
                |--------------------------------------------------------------------------
                | Date de signature
                |--------------------------------------------------------------------------
                */

                $position->signed_at =
                    $signature
                        ? $signature->signed_at
                        : null;

                return $position;
            }
        );

        return $positions;
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
        | Récupérer les positions
        |--------------------------------------------------------------------------
        */

        $positions = DocumentSignaturePosition::query()
            ->where('document_id', $document->id)
            ->where('file_id', $file->id)
            ->orderBy('page_number')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Enrichissement
        |--------------------------------------------------------------------------
        */

        $positions =
            $this->enrichPositions(
                $positions,
                $document->id
            );

        /*
        |--------------------------------------------------------------------------
        | Réponse
        |--------------------------------------------------------------------------
        */

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

        $currentUser =
            $request->get('user');

        if (
            !$currentUser ||
            !isset($currentUser['id'])
        ) {
            return response()->json([
                'message' =>
                    'Utilisateur authentifié introuvable.',
            ], 401);
        }

        $userId =
            $currentUser['id'];

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
        | Enregistrer / mettre à jour
        |--------------------------------------------------------------------------
        */

        $position =
            DocumentSignaturePosition::updateOrCreate(

                [
                    'document_id' =>
                        $document->id,

                    'signature_type' =>
                        $validated['signature_type'],

                    'user_id' =>
                        $userId,
                ],

                [
                    'file_id' =>
                        $file->id,

                    'user_id' =>
                        $userId,

                    'page_number' =>
                        $validated['page_number'],

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

        $position =
            $this->enrichPositions(
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
}