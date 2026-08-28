<?php

namespace App\Services\Common;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class FileManager
{
    /**
     * Ajouter un fichier à un modèle.
     */
    public function upload(
        Model $model,
        UploadedFile $file,
        string $type
    ): File {

        $path = $file->store(
            strtolower(class_basename($model)),
            'public'
        );

        return $model->files()->create([
            'path' => $path,
            'size' => $file->getSize(),
            'type' => $type,
            'page_count' => $this->getPageCount($file),
        ]);
    }


    /**
     * Remplacer un fichier existant.
     */
    public function replace(
        Model $model,
        string $type,
        UploadedFile $file
    ): File {

        $oldFile = $model->files()
            ->where('type', $type)
            ->first();

        if ($oldFile) {

            if (
                Storage::disk('public')
                    ->exists($oldFile->path)
            ) {
                Storage::disk('public')
                    ->delete($oldFile->path);
            }

            $oldFile->delete();
        }

        return $this->upload(
            $model,
            $file,
            $type
        );
    }


    /**
     * Supprimer un fichier.
     */
    public function delete(File $file): bool
    {
        if (
            Storage::disk('public')
                ->exists($file->path)
        ) {
            Storage::disk('public')
                ->delete($file->path);
        }

        return $file->delete();
    }


    /**
     * URL publique.
     */
    public function url(?File $file): ?string
    {
        if (!$file) {
            return null;
        }

        return Storage::url($file->path);
    }


    /**
     * Déterminer le nombre de pages d'un PDF.
     *
     * Retourne null si le fichier n'est pas un PDF
     * ou si le nombre de pages ne peut pas être déterminé.
     */
    protected function getPageCount(
        UploadedFile $file
    ): ?int {

        if (
            strtolower($file->getClientOriginalExtension())
            !== 'pdf'
        ) {
            return null;
        }

        try {

            $parser = new Parser();

            $pdf = $parser->parseFile(
                $file->getRealPath()
            );

            $pages = $pdf->getPages();

            return count($pages);

        } catch (\Throwable $e) {

            report($e);

            return null;
        }
    }
}