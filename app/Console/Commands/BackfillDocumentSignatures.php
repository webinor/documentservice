<?php

namespace App\Console\Commands;

use App\Models\DocumentSignature;
use App\Models\FileSignature;
use App\Models\Misc\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDocumentSignatures extends Command
{
    protected $signature = 'signatures:backfill-document
                            {--safe : Simuler la migration sans modifier la base}
                            {--exec : Exécuter réellement la migration}';

    protected $description =
        'Crée les document_signatures à partir des file_signatures existantes et renseigne document_signature_id';


    public function handle(): int
    {
        /*
        |--------------------------------------------------------------------------
        | Vérification du mode
        |--------------------------------------------------------------------------
        */

        $safe = $this->option('safe');
        $exec = $this->option('exec');

        if ($safe && $exec) {

            $this->error(
                'Les options --safe et --exec sont mutuellement exclusives.'
            );

            return self::FAILURE;
        }

        if (!$safe && !$exec) {

            $this->error(
                'Vous devez préciser --safe ou --exec.'
            );

            $this->newLine();

            $this->line(
                'Simulation :'
            );

            $this->line(
                'php artisan signatures:backfill-document --safe'
            );

            $this->newLine();

            $this->line(
                'Exécution réelle :'
            );

            $this->line(
                'php artisan signatures:backfill-document --exec'
            );

            return self::FAILURE;
        }


        /*
        |--------------------------------------------------------------------------
        | Récupération des file_signatures
        |--------------------------------------------------------------------------
        */

        $fileSignatures =
            FileSignature::query()
                ->with([
                    'file.model',
                ])
                ->whereNull('document_signature_id')
                ->orderBy('id')
                ->get();


        if ($fileSignatures->isEmpty()) {

            $this->info(
                'Aucune file_signature à traiter.'
            );

            return self::SUCCESS;
        }


        /*
        |--------------------------------------------------------------------------
        | Informations
        |--------------------------------------------------------------------------
        */

        $this->info(
            sprintf(
                '%d file_signature(s) à traiter.',
                $fileSignatures->count()
            )
        );

        $this->newLine();


        /*
        |--------------------------------------------------------------------------
        | MODE SAFE
        |--------------------------------------------------------------------------
        */

        if ($safe) {

            $this->warn(
                'MODE SAFE : aucune modification ne sera effectuée.'
            );

            $this->newLine();

            foreach ($fileSignatures as $fileSignature) {

                $result =
                    $this->resolveDocumentInformation(
                        $fileSignature
                    );


                if (!$result['success']) {

                    $this->warn(
                        sprintf(
                            'FileSignature #%d : %s',
                            $fileSignature->id,
                            $result['message']
                        )
                    );

                    continue;
                }


                $this->line(
                    sprintf(
                        'FileSignature #%d → File #%d → %s → Document #%d → User #%d',
                        $fileSignature->id,
                        $result['file_id'],
                        $result['model_type'],
                        $result['document_id'],
                        $fileSignature->user_id
                    )
                );
            }


            $this->newLine();

            $this->info(
                'Simulation terminée. Aucune donnée n’a été modifiée.'
            );

            return self::SUCCESS;
        }


        /*
        |--------------------------------------------------------------------------
        | MODE EXEC
        |--------------------------------------------------------------------------
        */

        $this->warn(
            'MODE EXEC : la base de données sera modifiée.'
        );

        $this->newLine();

        if (!$this->confirm(
            'Voulez-vous réellement continuer ?'
        )) {

            $this->info(
                'Opération annulée.'
            );

            return self::SUCCESS;
        }


        /*
        |--------------------------------------------------------------------------
        | Statistiques
        |--------------------------------------------------------------------------
        */

        $created = 0;
        $updated = 0;
        $skipped = 0;


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $fileSignatures,
            &$created,
            &$updated,
            &$skipped
        ) {

            foreach ($fileSignatures as $fileSignature) {

                /*
                |--------------------------------------------------------------------------
                | Résoudre le document
                |--------------------------------------------------------------------------
                */

                $result =
                    $this->resolveDocumentInformation(
                        $fileSignature
                    );


                if (!$result['success']) {

                    $this->warn(
                        sprintf(
                            'FileSignature #%d ignorée : %s',
                            $fileSignature->id,
                            $result['message']
                        )
                    );

                    $skipped++;

                    continue;
                }


                $documentId =
                    $result['document_id'];


                /*
                |--------------------------------------------------------------------------
                | Vérifier user_id
                |--------------------------------------------------------------------------
                */

                $userId =
                    $fileSignature->user_id;


                if (!$userId) {

                    $this->warn(
                        sprintf(
                            'FileSignature #%d ignorée : user_id absent.',
                            $fileSignature->id
                        )
                    );

                    $skipped++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Rechercher une signature métier existante
                |--------------------------------------------------------------------------
                |
                | Une même personne peut avoir plusieurs positions
                | de signature sur un même fichier/document.
                |
                | On ne doit donc créer qu'une seule
                | document_signature.
                |
                */

                $documentSignature =
                    DocumentSignature::query()
                        ->where(
                            'document_id',
                            $documentId
                        )
                        ->where(
                            'signed_by',
                            $userId
                        )
                        ->orderBy('id')
                        ->first();


                /*
                |--------------------------------------------------------------------------
                | Créer la signature métier
                |--------------------------------------------------------------------------
                */

                if (!$documentSignature) {

                    $documentSignature =
                        DocumentSignature::create([

                            'document_id' =>
                                $documentId,

                            'signature_type' =>
                                'RECEIPT',

                            'signed_by' =>
                                $userId,

                            'signature_file' =>
                                null,

                            'signed_at' =>
                                $fileSignature->signed_at
                                ?? now(),

                            'metadata' =>
                                null,
                        ]);


                    $created++;

                    $this->info(
                        sprintf(
                            'DocumentSignature #%d créée → Document #%d / User #%d',
                            $documentSignature->id,
                            $documentId,
                            $userId
                        )
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Rattacher la file_signature
                |--------------------------------------------------------------------------
                */

                $fileSignature->update([

                    'document_signature_id' =>
                        $documentSignature->id,

                ]);


                $updated++;
            }
        });


        /*
        |--------------------------------------------------------------------------
        | Résumé
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            'Migration terminée.'
        );

        $this->table(
            [
                'Élément',
                'Nombre',
            ],
            [
                [
                    'Document signatures créées',
                    $created,
                ],
                [
                    'File signatures mises à jour',
                    $updated,
                ],
                [
                    'File signatures ignorées',
                    $skipped,
                ],
            ]
        );


        return self::SUCCESS;
    }


    /**
     * Résout le document auquel appartient une FileSignature.
     *
     * Architecture actuelle :
     *
     * File
     *   └── model()
     *        └── RegularizationReceipt
     *             └── sheet()
     *                  └── document_id
     *
     * Mais la méthode essaie également de gérer
     * les modèles possédant directement document_id
     * ou une relation document().
     */
    protected function resolveDocumentInformation(
        FileSignature $fileSignature
    ): array {

        /*
        |--------------------------------------------------------------------------
        | File
        |--------------------------------------------------------------------------
        */

        $file =
            $fileSignature->file;


        if (!$file) {

            return [
                'success' => false,
                'message' => 'File introuvable.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Model polymorphique
        |--------------------------------------------------------------------------
        */

        $model =
            $file->model;


        if (!$model) {

            return [
                'success' => false,
                'message' => 'Modèle polymorphique du fichier introuvable.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Cas 1
        |--------------------------------------------------------------------------
        |
        | Le modèle possède directement document_id.
        |
        | Exemple :
        |
        | TaxiPaper
        | RegularizationSheet
        | etc.
        |
        */

        if (
            isset($model->document_id) &&
            $model->document_id
        ) {

            return [

                'success' => true,

                'file_id' =>
                    $file->id,

                'model_type' =>
                    get_class($model),

                'document_id' =>
                    (int) $model->document_id,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Cas 2
        |--------------------------------------------------------------------------
        |
        | Le modèle possède une relation document().
        |
        */

        if (
            method_exists(
                $model,
                'document'
            )
        ) {

            $document =
                $model->document;


            if (
                $document &&
                $document->id
            ) {

                return [

                    'success' => true,

                    'file_id' =>
                        $file->id,

                    'model_type' =>
                        get_class($model),

                    'document_id' =>
                        (int) $document->id,
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Cas 3
        |--------------------------------------------------------------------------
        |
        | RegularizationReceipt
        |
        | Receipt
        |   ↓
        | RegularizationSheet
        |   ↓
        | Document
        |
        */

        if (
            method_exists(
                $model,
                'sheet'
            )
        ) {

            $sheet =
                $model->sheet;


            if (!$sheet) {

                return [
                    'success' => false,
                    'message' =>
                        'La fiche de régularisation est introuvable.',
                ];
            }


            if (
                isset($sheet->document_id) &&
                $sheet->document_id
            ) {

                return [

                    'success' => true,

                    'file_id' =>
                        $file->id,

                    'model_type' =>
                        get_class($model),

                    'document_id' =>
                        (int) $sheet->document_id,
                ];
            }


            if (
                method_exists(
                    $sheet,
                    'document'
                )
            ) {

                $document =
                    $sheet->document;


                if (
                    $document &&
                    $document->id
                ) {

                    return [

                        'success' => true,

                        'file_id' =>
                            $file->id,

                        'model_type' =>
                            get_class($model),

                        'document_id' =>
                            (int) $document->id,
                    ];
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Aucun document trouvé
        |--------------------------------------------------------------------------
        */

        return [

            'success' => false,

            'message' =>
                sprintf(
                    'Impossible de déterminer le document pour le modèle %s.',
                    get_class($model)
                ),
        ];
    }
}