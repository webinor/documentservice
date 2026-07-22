<?php

namespace App\Console\Commands;

use App\Models\Misc\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FillDocumentsUuid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:fill-uuid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remplit les UUID des documents existants';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = 0;

        Document::whereNull('uuid')
            ->chunkById(500, function ($documents) use (&$count) {
                foreach ($documents as $document) {
                    $document->update([
                        'uuid' => (string) Str::uuid(),
                    ]);

                    $count++;
                }
            });

        $this->info("{$count} document(s) mis à jour.");

        return 0;
    }
}