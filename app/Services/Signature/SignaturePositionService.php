<?php

namespace App\Services\Signature;

use App\Models\DocumentSignature;
use App\Models\DocumentSignaturePosition;
use App\Models\Misc\Document;
use App\Models\Misc\File;
use App\Services\UserServiceClient;
use Illuminate\Support\Collection;
use RuntimeException;

class SignaturePositionService
{
    protected UserServiceClient $userService;

    public function __construct(
        UserServiceClient $userService
    ) {
        $this->userService = $userService;
    }

    /**
     * Récupérer toutes les positions enrichies
     * d'un document.
     */
    public function getEnrichedPositions(
        string $documentUuid
    ): Collection {

        $document = Document::query()
            ->where('uuid', $documentUuid)
            ->firstOrFail();

        $positions = DocumentSignaturePosition::query()
            ->where('document_id', $document->id)
            ->orderBy('file_id')
            ->orderBy('page_number')
            ->get();

        return $this->enrichPositions(
            $positions,
            $document->id
        );
    }

    /**
     * Récupérer les positions enrichies
     * d'un fichier.
     */
    public function getEnrichedFilePositions(
        string $documentUuid,
        int $fileId
    ): Collection {

        $document = Document::query()
            ->where('uuid', $documentUuid)
            ->firstOrFail();

        $positions = DocumentSignaturePosition::query()
            ->where('document_id', $document->id)
            ->where('file_id', $fileId)
            ->orderBy('page_number')
            ->get();

        return $this->enrichPositions(
            $positions,
            $document->id
        );
    }

    /**
     * Récupérer les fichiers ayant des positions
     * de signature, avec leurs positions enrichies.
     *
     * Structure retournée :
     *
     * [
     *     [
     *         'file_id' => 10,
     *         'file' => File,
     *         'positions' => Collection,
     *     ],
     *     ...
     * ]
     */
    public function getFilesWithPositions(
        string $documentUuid
    ): Collection {

        $document = Document::query()
            ->where('uuid', $documentUuid)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Positions
        |--------------------------------------------------------------------------
        */

        $positions = DocumentSignaturePosition::query()
            ->where('document_id', $document->id)
            ->orderBy('file_id')
            ->orderBy('page_number')
            ->get();

        if ($positions->isEmpty()) {
            return collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Enrichissement
        |--------------------------------------------------------------------------
        */

        $positions = $this->enrichPositions(
            $positions,
            $document->id
        );

        /*
        |--------------------------------------------------------------------------
        | Regroupement par fichier
        |--------------------------------------------------------------------------
        */

        return $positions
            ->groupBy('file_id')
            ->map(
                function (Collection $filePositions, $fileId) {

                    $file = File::query()
                        ->findOrFail($fileId);

                    return [
                        'file_id' => (int) $fileId,

                        'file' => $file,

                        'positions' => $filePositions
                            ->values(),
                    ];
                }
            )
            ->values();
    }

    /**
     * Enrichir les positions avec :
     *
     * - informations du signataire
     * - signature effective
     * - état de signature
     * - date de signature
     */
    public function enrichPositions(
        Collection $positions,
        int $documentId
    ): Collection {

        if ($positions->isEmpty()) {
            return $positions;
        }

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
        | Signataires depuis User Service
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
        | Signatures effectives
        |--------------------------------------------------------------------------
        */

        $signatures = DocumentSignature::query()
            ->where('document_id', $documentId)
            ->get();

        $signaturesByUserAndType = $signatures
            ->keyBy(function ($signature) {

                return
                    $signature->signature_type
                    . '_'
                    . $signature->signed_by;
            });

        /*
        |--------------------------------------------------------------------------
        | Enrichissement
        |--------------------------------------------------------------------------
        */

        return $positions->map(
            function ($position) use (
                $signatoriesById,
                $signaturesByUserAndType
            ) {

                $signatory =
                    $signatoriesById->get(
                        $position->user_id
                    );

                /*
                |--------------------------------------------------------------------------
                | Signature effective
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
                | Signataire
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
                | État
                |--------------------------------------------------------------------------
                */

                $position->is_signed =
                    $signature !== null
                    && $signature->signed_at !== null;

                /*
                |--------------------------------------------------------------------------
                | Date
                |--------------------------------------------------------------------------
                */

                $position->signed_at =
                    $signature
                        ? $signature->signed_at
                        : null;

                return $position;
            }
        );
    }
}