<?php

namespace App\Services\Common;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileManager
{

    /**
     * Ajouter un fichier à un modèle
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
        ]);
    }



    /**
     * Remplacer un fichier existant
     */
    public function replace(
        Model $model,
        string $type,
        UploadedFile $file
    ): File {


        // chercher ancien fichier
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
     * Supprimer un fichier
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
     * URL publique
     */
    public function url(?File $file): ?string
    {
        if (!$file) {
            return null;
        }

        return Storage::url($file->path);
    }
}