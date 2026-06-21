<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mission;
use App\Services\ReferenceGeneratorService;
use Illuminate\Support\Facades\Log;

class FillMissionReferences extends Command
{
    protected $signature = 'missions:fill-references 
                            {--dry-run : Simule sans enregistrer}
                            {--chunk=200 : Nombre de lignes par lot}';

    protected $description = 'Remplit les références manquantes des missions';

    protected $referenceService;

    public function __construct(ReferenceGeneratorService $referenceService)
    {
        parent::__construct();
        $this->referenceService = $referenceService;
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $this->info('--- Début traitement missions ---');

        if ($dryRun) {
            $this->warn('MODE DRY RUN activé (aucune modification sera enregistrée)');
        }

        $query = Mission::whereNull('code')
            ->orWhere('code', '');

        $total = $query->count();

        if ($total === 0) {
            $this->info('Aucune mission à traiter.');
            return 0;
        }

        $this->info("Total missions à traiter : {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;

        $query->chunk($chunkSize, function ($missions) use ($dryRun, &$processed, $bar) {

            foreach ($missions as $mission) {

                $code = $this->referenceService->generate('MISSION');

                if (!$dryRun) {
                    $mission->code = $code;
                    $mission->save();
                }

                Log::info('Mission code generated', [
                    'mission_id' => $mission->id,
                    'code' => $code,
                    'dry_run' => $dryRun
                ]);

                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();

        $this->newLine();
        $this->info("Terminé. Missions traitées : {$processed}");

        if ($dryRun) {
            $this->warn("Mode dry-run : aucune donnée n'a été modifiée.");
        }

        return 0;
    }
}