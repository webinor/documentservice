<?php

namespace App\Console\Commands;

use App\Models\Misc\Document;
use App\Services\UserServiceClient;
use Illuminate\Console\Command;

class SyncCreatorEmployeeIds extends Command
{
    protected $signature = 'documents:sync-creator-employees
                            {--document= : Synchroniser un document précis}
                            {--user= : Synchroniser uniquement les documents créés par cet utilisateur}
                            {--limit= : Limiter le nombre de documents traités}
                            {--test : Tester la synchronisation sans modifier la base}
                            {--exec : Exécuter réellement la synchronisation}';

    protected $description = 'Synchronise creator_employee_id des documents avec UserService';

    protected UserServiceClient $userService;

    /**
     * Cache user_id => employee_id.
     */
    protected array $employeeCache = [];

    public function __construct(UserServiceClient $userService)
    {
        parent::__construct();

        $this->userService = $userService;
    }

    public function handle()
    {
        /*
        |--------------------------------------------------------------------------
        | Vérification du mode
        |--------------------------------------------------------------------------
        */

        $test = $this->option('test');
        $exec = $this->option('exec');

        if (!$test && !$exec) {
            $this->error(
                'Vous devez préciser --test ou --exec.'
            );

            $this->newLine();

            $this->line('Exemples :');
            $this->line(
                '  php artisan documents:sync-creator-employees --test'
            );
            $this->line(
                '  php artisan documents:sync-creator-employees --exec'
            );

            return self::INVALID;
        }

        if ($test && $exec) {
            $this->error(
                'Les options --test et --exec sont mutuellement exclusives.'
            );

            return self::INVALID;
        }

        /*
        |--------------------------------------------------------------------------
        | Mode
        |--------------------------------------------------------------------------
        */

        if ($test) {
            $this->warn(
                'MODE TEST : aucune modification ne sera effectuée.'
            );
        }

        if ($exec) {
            $this->warn(
                'MODE EXECUTION : les documents seront réellement modifiés.'
            );
        }

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        $query = Document::query()
            ->whereNull('creator_employee_id')
            ->whereNotNull('created_by');

        if ($documentId = $this->option('document')) {
            $query->where('id', $documentId);
        }

        if ($userId = $this->option('user')) {
            $query->where('created_by', $userId);
        }

        /*
        |--------------------------------------------------------------------------
        | Limit
        |--------------------------------------------------------------------------
        */

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info(
                'Aucun document à synchroniser.'
            );

            return self::SUCCESS;
        }

        $this->info(
            "Documents à traiter : {$count}"
        );

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Counters
        |--------------------------------------------------------------------------
        */

        $updated = 0;
        $notFound = 0;
        $failed = 0;

        /*
        |--------------------------------------------------------------------------
        | Traitement
        |--------------------------------------------------------------------------
        */

        $query->chunkById(
            100,
            function ($documents) use (
                &$updated,
                &$notFound,
                &$failed,
                $exec
            ) {

                foreach ($documents as $document) {

                    $userId = (int) $document->created_by;

                    $this->line(
                        "Document #{$document->id} → user #{$userId}"
                    );

                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | Cache
                        |--------------------------------------------------------------------------
                        */

                        if (array_key_exists(
                            $userId,
                            $this->employeeCache
                        )) {

                            $employeeId =
                                $this->employeeCache[$userId];

                        } else {

                            $employeeId =
                                $this->userService
                                    ->getEmployeeIdByUser(
                                        $userId
                                    );

                            $this->employeeCache[$userId] =
                                $employeeId;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Employee introuvable
                        |--------------------------------------------------------------------------
                        */

                        if (!$employeeId) {

                            $this->warn(
                                "  → Aucun employé associé à user #{$userId}"
                            );

                            $notFound++;

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | TEST
                        |--------------------------------------------------------------------------
                        */

                        if (!$exec) {

                            $this->info(
                                "  → [TEST] creator_employee_id serait {$employeeId}"
                            );

                            $updated++;

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | EXECUTION
                        |--------------------------------------------------------------------------
                        */

                        $document->update([
                            'creator_employee_id' => $employeeId,
                        ]);

                        $this->info(
                            "  ✓ creator_employee_id = {$employeeId}"
                        );

                        $updated++;

                    } catch (\Throwable $e) {

                        $this->error(
                            "  ✗ {$e->getMessage()}"
                        );

                        $failed++;
                    }
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Résumé
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            $test
                ? 'Test terminé.'
                : 'Synchronisation terminée.'
        );

        $this->table(
            ['Statut', 'Nombre'],
            [
                [
                    $test
                        ? 'À mettre à jour'
                        : 'Mis à jour',
                    $updated,
                ],
                [
                    'Employé introuvable',
                    $notFound,
                ],
                [
                    'Erreurs',
                    $failed,
                ],
            ]
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}