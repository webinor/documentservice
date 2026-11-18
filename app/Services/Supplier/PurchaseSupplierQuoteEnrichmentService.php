<?php

namespace App\Services\Supplier;

use App\Models\PurchaseSupplierQuote;
use Illuminate\Support\Collection;

class PurchaseSupplierQuoteEnrichmentService
{
    private $supplierServiceClient;

    public function __construct(
        SupplierServiceClient $supplierServiceClient
    ) {
        $this->supplierServiceClient = $supplierServiceClient;
    }

    /**
     * Enrichit une collection de devis.
     *
     * Les fournisseurs sont récupérés en un seul appel groupé.
     *
     * @param Collection $quotes
     * @return Collection
     */
    public function enrichMany(Collection $quotes): Collection
    {
        if ($quotes->isEmpty()) {
            return $quotes;
        }

        $baseUrl = rtrim(config('app.url'), '/');

        /*
         * Récupération groupée des fournisseurs.
         */
        $supplierIds = $quotes
            ->pluck('supplier_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $suppliersById = empty($supplierIds)
            ? []
            : $this->supplierServiceClient->getSuppliersByIds(
                $supplierIds
            );

        /*
         * Enrichissement de chaque devis.
         */
        return $quotes->map(function ($quote) use (
            $suppliersById,
            $baseUrl
        ) {
            $supplierId = (int) $quote->supplier_id;

            $quote->supplier = $suppliersById[$supplierId] ?? null;

            return $this->enrichQuoteFiles($quote, $baseUrl);
        });
    }

    /**
     * Enrichit un seul devis.
     *
     * @param PurchaseSupplierQuote|null $quote
     * @return PurchaseSupplierQuote|null
     */
    public function enrichOne(?PurchaseSupplierQuote $quote)
    {
        if (!$quote) {
            return null;
        }

        return $this->enrichMany(
            collect([$quote])
        )->first();
    }

    /**
     * Enrichit les fichiers du devis.
     *
     * IMPORTANT :
     * Déplacer ici, sans modification, le contenu de la méthode
     * enrichQuoteFiles() actuellement présente dans le contrôleur.
     *
     * @param PurchaseSupplierQuote $quote
     * @param string $baseUrl
     * @return PurchaseSupplierQuote
     */
    private function enrichQuoteFiles(
        PurchaseSupplierQuote $quote,
        string $baseUrl
    ): PurchaseSupplierQuote {
       
    

    $quote->files = $quote->files->map(function ($file) use ($baseUrl) {

        /*
         * URLs du fichier original.
         */
        $file->view_url =
            "{$baseUrl}/api/documents/supplier-quote-files/{$file->path}/view";

        $file->download_url =
            "{$baseUrl}/api/documents/supplier-quote-files/{$file->path}/download";

        /*
         * URLs du fichier signé.
         */
        $file->signed_view_url = null;
        $file->signed_download_url = null;

        if (!empty($file->signed_path)) {
            $file->signed_view_url =
                "{$baseUrl}/api/documents/supplier-quote-files/{$file->signed_path}/view";

            $file->signed_download_url =
                "{$baseUrl}/api/documents/supplier-quote-files/{$file->signed_path}/download";
        }

        /*
         * Vérifier si des positions de signature
         * sont enregistrées pour ce fichier.
         */
        $file->has_signature_position =
            $file->signaturePositions()->exists();

        return $file;
    });

    return $quote;
    }
}