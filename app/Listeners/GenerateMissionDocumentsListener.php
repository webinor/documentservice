<?php

namespace App\Listeners;

use App\Events\MissionValidated;
use App\Services\Mission\MissionDocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GenerateMissionDocumentsListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MissionValidated  $event
     * @return void
     */
    public function handle(MissionValidated $event): void
    {
        $mission = $event->mission;

        app(MissionDocumentService::class)
            ->generateAll($mission);
    }
}
