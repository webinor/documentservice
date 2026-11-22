<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class StoredFileController extends Controller
{
   /**
 * Retourne le chemin réel d'un fichier stocké.
 *
 * Recherche dans les dossiers connus du service.
 *
 * @param string $path
 * @return string|null
 */
private function resolveFilePath($path)
{
    $disk = Storage::disk('public');

    // Dossiers dans lesquels rechercher le fichier.
    $directories = [
        '',
        'documents_attachments',
        'document-attachments',
    ];

    foreach ($directories as $directory) {

        $filePath = $directory
            ? $directory . '/' . ltrim($path, '/')
            : $path;

        if ($disk->exists($filePath)) {
            return $filePath;
        }
    }

    return null;
}

/**
 * Afficher un fichier stocké.
 *
 * @param string $path
 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
 */
public function view($path)
{
    $filePath = $this->resolveFilePath($path);

    abort_unless(
        $filePath,
        404,
        'Fichier introuvable.'
    );

    return response()->file(
        Storage::disk('public')->path($filePath)
    );
}

/**
 * Télécharger un fichier stocké.
 *
 * @param string $path
 * @return \Symfony\Component\HttpFoundation\StreamedResponse
 */
public function download($path)
{
    $filePath = $this->resolveFilePath($path);

    abort_unless(
        $filePath,
        404,
        'Fichier introuvable.'
    );

    return Storage::disk('public')->download($filePath);
}
}