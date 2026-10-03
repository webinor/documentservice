<?php

namespace App\Http\Controllers;

use App\Models\DocumentVerification;
use App\Services\Document\DocumentService;
use Illuminate\Http\JsonResponse;

class DocumentVerificationController extends Controller
{
    public function verify(
        string $verificationCode,
        DocumentService $documentService
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | 1. Récupération de la dernière vérification
        |--------------------------------------------------------------------------
        |
        | Un même document peut avoir plusieurs enregistrements de
        | vérification. On prend toujours le dernier enregistrement
        | correspondant au code de vérification.
        |
        */

        $verification = DocumentVerification::query()
            ->where(
                'verification_code',
                $verificationCode
            )
            ->latest('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | 2. Code introuvable
        |--------------------------------------------------------------------------
        */

        if (!$verification) {

            return response()->json([
                'valid' => false,
                'status' => 'NOT_FOUND',
                'message' => 'Code de vérification invalide.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Vérification de la validité
        |--------------------------------------------------------------------------
        */

        if (!$verification->is_valid) {

            return response()->json([
                'valid' => false,
                'status' => 'INVALID',
                'message' => 'Ce document n’est plus valide.',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Récupération du document réel
        |--------------------------------------------------------------------------
        */

        try {

            $document = $documentService->getDoc(
                $verification->document_id
            );

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'valid' => false,
                'status' => 'DOCUMENT_UNAVAILABLE',
                'message' =>
                    'Le document associé à ce code est actuellement indisponible.',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Document introuvable
        |--------------------------------------------------------------------------
        */

        if (!$document) {

            return response()->json([
                'valid' => false,
                'status' => 'DOCUMENT_NOT_FOUND',
                'message' =>
                    'Le document associé à ce code est introuvable.',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Enrichissement officiel du document
        |--------------------------------------------------------------------------
        |
        | L'enrichissement permet notamment de récupérer actor_details
        | depuis UserService.
        |
        */

        try {

            $enrichedDocument =
                $documentService->enrichDocument(
                    $document
                );

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'valid' => false,
                'status' => 'ENRICHMENT_ERROR',
                'message' =>
                    'Les informations du document ne peuvent pas être vérifiées actuellement.',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Bénéficiaire officiel
        |--------------------------------------------------------------------------
        */

        $actorDetails = data_get(
            $enrichedDocument,
            'actor_details'
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Lecture du snapshot
        |--------------------------------------------------------------------------
        |
        | Le snapshot de cette dernière vérification contient l'état
        | du document au moment où cette vérification a été enregistrée.
        |
        */

        $snapshot = $verification->snapshot;

        /*
        |--------------------------------------------------------------------------
        | 9. Décodage du snapshot
        |--------------------------------------------------------------------------
        */

        if (is_string($snapshot)) {

            $snapshot = json_decode(
                $snapshot,
                true
            );
        }

        if (!is_array($snapshot)) {
            $snapshot = [];
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Type du document
        |--------------------------------------------------------------------------
        |
        | Le type affiché publiquement vient du dernier snapshot.
        |
        */

        $documentType = data_get(
            $snapshot,
            'type'
        );

        /*
        |--------------------------------------------------------------------------
        | 11. Date d'émission
        |--------------------------------------------------------------------------
        |
        | La date affichée publiquement vient également du snapshot.
        |
        */

        $generatedAt = data_get(
            $snapshot,
            'generated_at'
        );

        /*
        |--------------------------------------------------------------------------
        | 12. Réponse publique
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'valid' => true,

            'status' => 'VALID',

            /*
            |--------------------------------------------------------------------------
            | Informations de vérification
            |--------------------------------------------------------------------------
            */

            'verification' => [

                'code' =>
                    $verification->verification_code,

                'version' =>
                    $verification->version,

                'verified_at' =>
                    now()->toISOString(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Informations du document
            |--------------------------------------------------------------------------
            */

            'document' => [

                'id' =>
                    data_get(
                        $enrichedDocument,
                        'id'
                    ),

                /*
                 * IMPORTANT :
                 * Ces deux valeurs viennent du dernier snapshot.
                 */
                'type' =>
                    $documentType,

                'reference' =>
                    data_get(
                        $enrichedDocument,
                        'reference'
                    ),

                'generated_at' =>
                    $generatedAt,

                /*
                 * Informations officielles de la personne
                 * associée au document.
                 */
                'beneficiary' =>
                    $actorDetails,

                'beneficiaire' =>
                    $actorDetails,
            ],
        ]);
    }
}