<?php

namespace App\Console\Commands;

use App\Models\Misc\Document;
use App\Models\RegularizationReceipt;
use App\Models\RegularizationSheet;
use App\Services\WorkflowServiceClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReconcileRegularizationReceipts extends Command
{
    /**
     * Utilisation :
     *
     * Simulation :
     * php artisan regularization-receipts:reconcile
     * php artisan regularization-receipts:reconcile --safe
     * php artisan regularization-receipts:reconcile --safe --chunk=50
     *
     * Exécution :
     * php artisan regularization-receipts:reconcile --exec
     * php artisan regularization-receipts:reconcile --exec --chunk=50
     */
    protected $signature = 'regularization-receipts:reconcile
                            {--safe : Simule les corrections sans modifier la base}
                            {--exec : Applique réellement les corrections}
                            {--chunk=100 : Nombre de documents par lot}';

    protected $description =
        'Réconcilie les justificatifs physiques des fiches à régulariser ayant un workflow admissible.';

    /**
     * Client du Workflow Service.
     *
     * @var WorkflowServiceClient
     */
    protected $workflowServiceClient;

    /**
     * Statistiques de réconciliation.
     *
     * @var array
     */
    protected $stats = [];

    /**
     * Indique si les modifications doivent être appliquées.
     *
     * @var bool
     */
    protected $executeChanges = false;

    public function __construct(
        WorkflowServiceClient $workflowServiceClient
    ) {
        parent::__construct();

        $this->workflowServiceClient = $workflowServiceClient;
    }

    /**
     * Point d'entrée de la commande Artisan.
     *
     * @return int
     */
    public function handle()
    {
        /*
         * Les options SAFE et EXEC ne peuvent pas être utilisées ensemble.
         */
        if ($this->option('safe') && $this->option('exec')) {
            $this->error(
                'Options incompatibles : utilisez --safe ou --exec, pas les deux.'
            );

            return 1;
        }

        /*
         * Par défaut, la commande fonctionne en mode SAFE.
         */
        $this->executeChanges = (bool) $this->option('exec');

        $chunkSize = (int) $this->option('chunk');

        if ($chunkSize < 1 || $chunkSize > 100) {
            $this->error(
                'La taille du lot doit être comprise entre 1 et 100.'
            );

            return 1;
        }

        $this->initializeStats();

        $this->newLine();

        $this->info(
            'RÉCONCILIATION DES JUSTIFICATIFS PHYSIQUES DES FAR'
        );

        $this->line(
            'Mode : '
            . ($this->executeChanges ? 'EXÉCUTION' : 'SIMULATION SAFE')
        );

        $this->line('Taille des lots : ' . $chunkSize);

        $this->comment(
            'Critères Workflow : statut COMPLETE ou signature de régularisation.'
        );

        if (!$this->executeChanges) {
            $this->comment(
                'Mode SAFE : aucune modification ne sera effectuée.'
            );
        }

        /*
         * Sélection des documents FAR.
         *
         * On utilise le code métier du type de document.
         */
        $documentQuery = Document::query()
            ->whereHas('document_type', function ($query) {
                $query->where('code', 'REGULARIZATION_SHEET');
            })
            ->with([
                'document_type',
                'regularization_sheet.receipts',
            ]);

        $this->stats['documents_far'] = (clone $documentQuery)->count();

        if ($this->stats['documents_far'] === 0) {
            $this->warn(
                'Aucun document de type REGULARIZATION_SHEET trouvé.'
            );

            $this->displaySummary();

            return 0;
        }

        $this->info(
            'Documents FAR trouvés : '
            . $this->stats['documents_far']
        );

        /*
         * Traitement par lots.
         *
         * Un appel batch au Workflow Service est effectué par lot,
         * et non un appel HTTP par document.
         */
        $documentQuery->chunkById(
            $chunkSize,
            function ($documents) {
                $this->processChunk($documents);
            }
        );

        $this->displaySummary();

        return $this->stats['errors'] > 0 ? 1 : 0;
    }

    /**
     * Initialise les compteurs.
     *
     * @return void
     */
    protected function initializeStats()
    {
        $this->stats = [
            'documents_far' => 0,
            'documents_examined' => 0,
            'workflows_admissibles' => 0,
            'sheets_without_model' => 0,
            'documents_without_workflow' => 0,
            'documents_without_admissible_workflow' => 0,
            'documents_with_duplicate_workflows' => 0,
            'documents_with_uuid_mismatch' => 0,
            'sheets_without_receipts' => 0,
            'receipts_total' => 0,
            'receipts_already_received' => 0,
            'receipts_to_update' => 0,
            'receipts_updated' => 0,
            'receipts_skipped' => 0,
            'errors' => 0,
        ];
    }

    /**
     * Traite un lot de documents.
     *
     * @param \Illuminate\Support\Collection $documents
     * @return void
     */
    protected function processChunk($documents)
    {
        $this->stats['documents_examined'] += $documents->count();

        $documentIds = $documents
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        /*
         * Récupération batch des instances admissibles.
         *
         * Le Workflow Service doit retourner uniquement les instances :
         * - dont le statut est COMPLETE ;
         * - ou possédant une signature REGULARIZATION_ADVANCE
         *   ou REGULARIZATION_SETTLEMENT.
         */
        try {
            $workflowInstances =
                $this->workflowServiceClient
                    ->getInstancesByDocumentIds($documentIds);
        } catch (Throwable $exception) {
            $this->stats['errors']++;

            $this->error(
                'Impossible de vérifier les workflows du lot : '
                . implode(', ', $documentIds)
            );

            $this->line(
                'Erreur : ' . $exception->getMessage()
            );

            /*
             * Aucun document de ce lot ne sera modifié.
             */
            foreach ($documents as $document) {
                $this->line(
                    '  Document #' . $document->id
                    . ' | UUID : ' . ($document->uuid ?: 'NULL')
                    . ' | IGNORÉ : workflow non vérifié.'
                );
            }

            return;
        }

        /*
         * Une réponse non conforme ne doit pas être interprétée comme
         * une absence de workflow.
         */
        if (!is_array($workflowInstances)) {
            $this->stats['errors']++;

            $this->error(
                'Réponse invalide du Workflow Service : '
                . 'un tableau d’instances était attendu.'
            );

            return;
        }

        /*
         * Regroupement des workflows par document_id.
         */
        $workflowsByDocument = [];

        foreach ($workflowInstances as $workflow) {
            if (
                !is_array($workflow)
                || !isset($workflow['document_id'])
                || !is_numeric($workflow['document_id'])
            ) {
                $this->stats['errors']++;

                $this->error(
                    'Réponse du Workflow Service invalide : '
                    . 'une instance ne contient pas de document_id valide.'
                );

                continue;
            }

            $workflowDocumentId = (int) $workflow['document_id'];

            if (!isset($workflowsByDocument[$workflowDocumentId])) {
                $workflowsByDocument[$workflowDocumentId] = [];
            }

            $workflowsByDocument[$workflowDocumentId][] = $workflow;
        }

        /*
         * Traitement individuel des documents.
         */
        foreach ($documents as $document) {
            $this->processDocument(
                $document,
                isset($workflowsByDocument[$document->id])
                    ? $workflowsByDocument[$document->id]
                    : []
            );
        }
    }

    /**
     * Traite une fiche à régulariser.
     *
     * Le statut du workflow n'est pas revérifié ici :
     * le Workflow Service a déjà filtré les instances admissibles.
     *
     * @param Document $document
     * @param array $workflows
     * @return void
     */
    protected function processDocument(
        Document $document,
        array $workflows
    ) {
        $this->newLine();

        $this->line(str_repeat('-', 78));

        $this->info(
            'Document #' . $document->id
            . ' | UUID : ' . ($document->uuid ?: 'NULL')
        );

        $this->line(
            'Type : '
            . optional($document->document_type)->code
        );

        /*
         * Vérification de l'existence de la fiche métier.
         */
        $sheet = $document->regularization_sheet;

        if (!$sheet) {
            $this->stats['sheets_without_model']++;

            $this->warn(
                'ANOMALIE : aucune fiche RegularizationSheet associée.'
            );

            return;
        }

        $this->line(
            'Fiche de régularisation #' . $sheet->id
        );

        /*
         * On conserve uniquement les instances du type métier
         * regularization_sheet.
         */
        $matchingTypeWorkflows = array_values(
            array_filter(
                $workflows,
                function ($workflow) {
                    return isset(
                        $workflow['document_type_relation_name']
                    )
                    && $workflow['document_type_relation_name']
                        === 'regularization_sheet';
                }
            )
        );

        /*
         * Aucune instance admissible du bon type.
         */
        if (empty($matchingTypeWorkflows)) {
            if (empty($workflows)) {
                $this->stats['documents_without_workflow']++;

                $this->warn(
                    'ANOMALIE : aucune instance de workflow admissible trouvée.'
                );
            } else {
                $this->stats[
                    'documents_without_admissible_workflow'
                ]++;

                $this->warn(
                    'ANOMALIE : aucune instance admissible de type '
                    . 'regularization_sheet parmi les instances retournées.'
                );

                $this->displayWorkflows($workflows);
            }

            return;
        }

        $this->displayWorkflows($matchingTypeWorkflows);

        /*
         * Plusieurs instances admissibles pour le même document :
         * on ne choisit pas arbitrairement laquelle utiliser.
         */
        if (count($matchingTypeWorkflows) > 1) {
            $this->stats[
                'documents_with_duplicate_workflows'
            ]++;

            $this->warn(
                'ANOMALIE : plusieurs instances admissibles pour ce document.'
            );

            $this->warn(
                'Aucune correction automatique pour cette fiche.'
            );

            return;
        }

        /*
         * Une seule instance admissible.
         */
        $workflow = $matchingTypeWorkflows[0];

        /*
         * Vérification de l'identité du document par UUID.
         */
        $documentUuid = (string) $document->uuid;

        $workflowUuid = isset($workflow['document_uuid'])
            ? (string) $workflow['document_uuid']
            : '';

        if (
            $documentUuid === ''
            || $workflowUuid === ''
            || $documentUuid !== $workflowUuid
        ) {
            $this->stats['documents_with_uuid_mismatch']++;

            $this->stats['errors']++;

            $this->warn(
                'ANOMALIE : UUID absent ou différent entre le document '
                . 'et le workflow.'
            );

            $this->line(
                'UUID document : '
                . ($documentUuid !== '' ? $documentUuid : 'NULL')
            );

            $this->line(
                'UUID workflow : '
                . ($workflowUuid !== '' ? $workflowUuid : 'NULL')
            );

            $this->warn(
                'Aucune correction effectuée pour cette fiche.'
            );

            return;
        }

        $this->stats['workflows_admissibles']++;

        $this->line(
            'Workflow retenu : #'
            . (isset($workflow['id']) ? $workflow['id'] : 'INCONNU')
            . ' | status = '
            . (isset($workflow['status']) ? $workflow['status'] : 'NULL')
        );

        /*
         * Vérification des justificatifs.
         */
        $receipts = $sheet->receipts;

        if ($receipts->isEmpty()) {
            $this->stats['sheets_without_receipts']++;

            $this->warn(
                'ANOMALIE : cette fiche ne possède aucun justificatif.'
            );

            return;
        }

        $this->stats['receipts_total'] += $receipts->count();

        $this->line(
            'Nombre de justificatifs : ' . $receipts->count()
        );

        foreach ($receipts as $receipt) {
            $this->reportReceipt($receipt);
        }

        /*
         * Seuls les justificatifs dont l'état n'est pas true
         * sont considérés comme candidats à la réconciliation.
         */
        $pendingReceipts = $receipts->filter(
            function ($receipt) {
                return $receipt->physical_receipt_received !== true;
            }
        );

        $alreadyReceivedCount =
            $receipts->count() - $pendingReceipts->count();

        $this->stats['receipts_already_received'] +=
            $alreadyReceivedCount;

        $this->stats['receipts_to_update'] +=
            $pendingReceipts->count();

        if ($pendingReceipts->isEmpty()) {
            $this->info(
                'Aucun changement : tous les justificatifs sont déjà reçus.'
            );

            return;
        }

        /*
         * Mode SAFE : rapport uniquement.
         */
        if (!$this->executeChanges) {
            $this->comment(
                'SAFE : ' . $pendingReceipts->count()
                . ' justificatif(s) seraient marqués comme reçus.'
            );

            return;
        }

        /*
         * Mode EXEC : application réelle.
         */
        $this->executeReceiptUpdates(
            $sheet,
            $pendingReceipts
        );
    }

    /**
     * Affiche les informations des instances retournées.
     *
     * @param array $workflows
     * @return void
     */
    protected function displayWorkflows(array $workflows)
    {
        foreach ($workflows as $workflow) {
            $this->line(
                'Workflow : #'
                . (isset($workflow['id']) ? $workflow['id'] : 'INCONNU')
                . ' | document_id = '
                . (isset($workflow['document_id'])
                    ? $workflow['document_id']
                    : 'INCONNU')
                . ' | UUID = '
                . (isset($workflow['document_uuid'])
                    ? $workflow['document_uuid']
                    : 'NULL')
                . ' | type = '
                . (isset($workflow['document_type_relation_name'])
                    ? $workflow['document_type_relation_name']
                    : 'NULL')
                . ' | status = '
                . (isset($workflow['status'])
                    ? $workflow['status']
                    : 'NULL')
            );
        }
    }

    /**
     * Affiche l'état actuel d'un justificatif.
     *
     * @param RegularizationReceipt $receipt
     * @return void
     */
    protected function reportReceipt(
        RegularizationReceipt $receipt
    ) {
        /*
         * On compare explicitement à true/false.
         * Cela suppose que le modèle convertit correctement
         * physical_receipt_received en booléen.
         */
        $received = $receipt->physical_receipt_received === true;

        if ($received) {
            $status = 'REÇU';
        } elseif ($receipt->physical_receipt_received === false) {
            $status = 'NON REÇU';
        } else {
            $status = 'NON RENSEIGNÉ';
        }

        $this->line(
            '  Justificatif #' . $receipt->id
            . ' | code = ' . ($receipt->code ?: 'NULL')
            . ' | référence = ' . ($receipt->reference ?: 'NULL')
            . ' | état = ' . $status
            . ' | reçu le = '
            . ($receipt->physical_receipt_received_at ?: 'NULL')
            . ' | reçu par = '
            . (
                $receipt->physical_receipt_received_by === null
                    ? 'NULL'
                    : $receipt->physical_receipt_received_by
            )
        );
    }

    /**
     * Met à jour les justificatifs candidats dans une transaction.
     *
     * Les lignes sont rechargées et verrouillées avant modification.
     *
     * @param RegularizationSheet $sheet
     * @param \Illuminate\Support\Collection $pendingReceipts
     * @return void
     */
    protected function executeReceiptUpdates(
        RegularizationSheet $sheet,
        $pendingReceipts
    ) {
        try {
            $result = DB::transaction(
                function () use ($sheet, $pendingReceipts) {
                    $receiptIds = $pendingReceipts
                        ->pluck('id')
                        ->map(function ($id) {
                            return (int) $id;
                        })
                        ->values()
                        ->all();

                    $lockedReceipts = RegularizationReceipt::query()
                        ->where(
                            'regularization_sheet_id',
                            $sheet->id
                        )
                        ->whereIn('id', $receiptIds)
                        ->lockForUpdate()
                        ->get();

                    $updated = 0;
                    $skipped = 0;

                    foreach ($lockedReceipts as $receipt) {
                        /*
                         * Vérification après acquisition du verrou.
                         */
                        if ($receipt->physical_receipt_received === true) {
                            $skipped++;

                            continue;
                        }

                        $receipt->physical_receipt_received = true;

                        /*
                         * Une date existante est conservée.
                         *
                         * Si elle est absente, on enregistre la date
                         * de réconciliation. Cette date ne prouve pas
                         * à elle seule la date réelle de réception physique.
                         */
                        if (
                            $receipt->physical_receipt_received_at === null
                        ) {
                            $receipt->physical_receipt_received_at = now();
                        }

                        /*
                         * L'identité de l'employé n'étant pas connue,
                         * physical_receipt_received_by reste inchangé.
                         */
                        $receipt->save();

                        $updated++;
                    }

                    return [
                        'updated' => $updated,
                        'skipped' => $skipped,
                    ];
                }
            );

            $this->stats['receipts_updated'] +=
                $result['updated'];

            $this->stats['receipts_skipped'] +=
                $result['skipped'];

            $this->info(
                'EXÉCUTION : '
                . $result['updated']
                . ' justificatif(s) mis à jour, '
                . $result['skipped']
                . ' ignoré(s).'
            );
        } catch (Throwable $exception) {
            $this->stats['errors']++;

            $this->error(
                'Erreur pendant la mise à jour de la fiche #'
                . $sheet->id
                . ' : '
                . $exception->getMessage()
            );
        }
    }

    /**
     * Affiche le bilan global.
     *
     * @return void
     */
    protected function displaySummary()
    {
        $this->newLine();

        $this->line(str_repeat('=', 78));

        $this->info('RAPPORT FINAL DE RÉCONCILIATION');

        $this->line(str_repeat('=', 78));

        $rows = [
            [
                'Documents FAR trouvés',
                $this->stats['documents_far'],
            ],
            [
                'Documents examinés',
                $this->stats['documents_examined'],
            ],
            [
                'Workflows admissibles retenus',
                $this->stats['workflows_admissibles'],
            ],
            [
                'Fiches sans modèle associé',
                $this->stats['sheets_without_model'],
            ],
            [
                'Documents sans workflow admissible retourné',
                $this->stats['documents_without_workflow'],
            ],
            [
                'Documents sans workflow admissible du bon type',
                $this->stats['documents_without_admissible_workflow'],
            ],
            [
                'Documents avec plusieurs workflows admissibles',
                $this->stats['documents_with_duplicate_workflows'],
            ],
            [
                'Documents avec UUID absent ou différent',
                $this->stats['documents_with_uuid_mismatch'],
            ],
            [
                'Fiches sans justificatif',
                $this->stats['sheets_without_receipts'],
            ],
            [
                'Justificatifs examinés',
                $this->stats['receipts_total'],
            ],
            [
                'Justificatifs déjà reçus',
                $this->stats['receipts_already_received'],
            ],
            [
                'Justificatifs candidats à la mise à jour',
                $this->stats['receipts_to_update'],
            ],
            [
                'Justificatifs effectivement mis à jour',
                $this->stats['receipts_updated'],
            ],
            [
                'Justificatifs ignorés pendant la mise à jour',
                $this->stats['receipts_skipped'],
            ],
            [
                'Erreurs',
                $this->stats['errors'],
            ],
        ];

        $this->table(
            ['Indicateur', 'Valeur'],
            $rows
        );

        $this->newLine();

        if ($this->executeChanges) {
            $this->info(
                'Mode EXÉCUTION terminé. Consultez les anomalies et les erreurs.'
            );
        } else {
            $this->comment(
                'Mode SAFE terminé : aucune modification n’a été effectuée.'
            );

            $this->comment(
                'Après vérification, utilisez --exec pour appliquer les corrections.'
            );
        }
    }
}