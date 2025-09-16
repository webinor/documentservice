<?php

namespace App\Jobs;

use App\Models\Misc\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchiveDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $documentId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $document = Document::find($this->documentId);
        if (!$document || $document->archived) return;

        // Vérifier que le workflow est terminé
        if (!in_array($document->status, ['COMPLETE', 'REJECTED'])) return;

        $filePath = storage_path('app/public/documents/' . $document->file_name);
        if (!file_exists($filePath)) return;

        // Calculer le checksum SHA-256
        $checksum = hash_file('sha256', $filePath);

        // Déplacer vers le dossier archive
        $archivePath = 'archives/' . $document->file_name;
        Storage::disk('public')->move('documents/' . $document->file_name, $archivePath);

        // Mettre à jour le document
        $document->update([
            'archived' => true,
            'archived_at' => now(),
            'checksum' => $checksum,
            'file_path' => $archivePath,
        ]);

        // Optionnel : log ou notification
        Log::info("Document {$document->id} archivé avec succès.");

    }
}
