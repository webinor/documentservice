<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class StoredFileController extends Controller
{
    /**
     * Afficher un fichier stocké.
     *
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function view($path)
    {
        abort_unless(
            Storage::disk('public')->exists($path),
            404,
            'Fichier introuvable.'
        );

        return response()->file(
            Storage::disk('public')->path($path)
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
        abort_unless(
            Storage::disk('public')->exists($path),
            404,
            'Fichier introuvable.'
        );

        return Storage::disk('public')->download($path);
    }
}