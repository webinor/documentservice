<?php

namespace App\Console\Commands;

use App\Models\RegularizationItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillRegularizationItems extends Command
{
    protected $signature = 'regularization:backfill-items {--execute : Persist changes}';

    protected $description = 'Populate planned/actual fields of regularization_items from existing data';

    public function handle(): int
    {
        $execute = $this->option('execute');

        $items = RegularizationItem::with('regularizationSheet')->get();

        if ($items->isEmpty()) {
            $this->warn('Aucune ligne trouvée.');
            return self::SUCCESS;
        }

        $this->info(
            $execute
                ? 'Mode EXECUTION'
                : 'Mode DRY-RUN (aucune donnée ne sera modifiée)'
        );

        $this->output->progressStart($items->count());

        DB::beginTransaction();

        try {

            foreach ($items as $item) {

                $sheet = $item->regularizationSheet;

                if (!$sheet) {
                    $this->newLine();
                    $this->warn("Item #{$item->id} ignoré (sheet absente).");
                    $this->output->progressAdvance();
                    continue;
                }

                $plannedQuantity = 1;
                $actualQuantity  = 1;

                $plannedAmount = $sheet->amount;
                $actualAmount  = $item->unit_price;

                if ($execute) {

                    $item->update([
                        'planned_quantity' => $plannedQuantity,
                        'actual_quantity'  => $actualQuantity,
                        'planned_amount'   => $plannedAmount,
                        'actual_amount'    => $actualAmount,
                    ]);

                } else {

                    $this->newLine();

                    $this->line(sprintf(
                        'Item #%d | Sheet #%d | planned=%s | actual=%s',
                        $item->id,
                        $sheet->id,
                        $plannedAmount,
                        $actualAmount
                    ));
                }

                $this->output->progressAdvance();
            }

            $this->output->progressFinish();

            if ($execute) {
                DB::commit();
                $this->newLine();
                $this->info('Migration terminée avec succès.');
            } else {
                DB::rollBack();
                $this->newLine();
                $this->comment('Aucune modification effectuée.');
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {

            DB::rollBack();

            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}