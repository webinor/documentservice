<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class SupplierQuoteFileController extends Controller
{
    /**
     * Afficher un fichier associé à un devis fournisseur.
     *
     * GET /supplier-quote-files/{path}/view
     *
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function view($path)
    {
        /*
         * Vérifier que le fichier existe dans le stockage public.
         */
        abort_unless(
            Storage::disk('public')->exists($path),
            404,
            'Fichier du devis fournisseur introuvable.'
        );

        /*
         * Retourner le fichier pour affichage dans le navigateur.
         */
        return response()->file(
            Storage::disk('public')->path($path)
        );
    }

    /**
     * Télécharger un fichier associé à un devis fournisseur.
     *
     * GET /supplier-quote-files/{path}/download
     *
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download($path)
    {
        /*
         * Vérifier que le fichier existe dans le stockage public.
         */
        abort_unless(
            Storage::disk('public')->exists($path),
            404,
            'Fichier du devis fournisseur introuvable.'
        );

        /*
         * Retourner le fichier en téléchargement.
         */
        return Storage::disk('public')->download($path);
    }
}