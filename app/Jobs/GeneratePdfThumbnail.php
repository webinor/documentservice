<?php
namespace App\Jobs;

use App\Models\Misc\Attachment;
use App\Models\Misc\Document;
use App\Models\Misc\File;
use App\Models\Thumbnail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Facades\Storage;

class GeneratePdfThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $attachment;

    /**
     * Create a new job instance.
     */
    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Chemin du PDF dans le storage
        //dd($this->attachment->file->path);
        $pdfPath = Storage::path("public/documents_attachments/".$this->attachment->file->path); // ex: 'documents/facture.pdf'
        //dd($pdfPath);

        // Générer l'image de la première page
        $pdf = new Pdf($pdfPath);
        $pdf->setPage(1);

        $thumbnailName = pathinfo($pdfPath, PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbnailPath = storage_path('app/public/thumbnails/' . $thumbnailName);
        //$thumbnailSize = pathinfo($pdfPath, PATHINFO_FILESIZE) . '_thumb.jpg';

        // Créer le dossier si nécessaire
        if (!file_exists(dirname($thumbnailPath))) {
            mkdir(dirname($thumbnailPath), 0755, true);
        }

        // Sauvegarder l'image
        $pdf->saveImage($thumbnailPath);

        // Optionnel : stocker le chemin dans la DB

        $thumbnail = new Thumbnail();
        $this->attachment->thumbnail()->save($thumbnail);

        $file = new File();
        $file->path = $thumbnailName  ; 
        $file->type =  "jpg" ; 
        $file->size = 1234;//$thumbnailName->getSize()  ; 

        $thumbnail->file()->save($file);

      //  $this->document->thumbnail_path = 'thumbnails/' . $thumbnailName;
     //   $this->document->save();








      
    }
}
