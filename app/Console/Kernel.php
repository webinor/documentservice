<?php

namespace App\Console;

use App\Jobs\ArchiveDocumentJob;
use App\Models\Misc\Document;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
           // Exécuter tous les jours à minuit
    $schedule->call(function () {
        $documents = Document::whereIn('status', ['COMPLETE', 'REJECTED'])
                             ->where('archived', false)
                             ->get();

        foreach ($documents as $doc) {
            ArchiveDocumentJob::dispatch($doc->id);
        }
    })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
