<?php

namespace App\Services\Supplier;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupplierServiceClient
{


/**
 * Récupère plusieurs fournisseurs en une seule requête HTTP.
 *
 * Les fournisseurs retournés sont indexés par leur identifiant.
 *
 * Exemple :
 * [
 *     12 => [
 *         'id' => 12,
 *         'name' => 'Fournisseur A',
 *         'dueDate' => '2026-10-30',
 *     ],
 * ]
 *
 * En cas d'indisponibilité du service, retourne un tableau vide.
 *
 * @param array $supplierIds
 * @return array
 */
public function getSuppliersByIds(array $supplierIds): array
{
    // Nettoie les identifiants et supprime les doublons.
    $supplierIds = array_values(array_unique(array_filter(
        array_map('intval', $supplierIds),
        function ($id) {
            return $id > 0;
        }
    )));

    if (empty($supplierIds)) {
        return [];
    }

    $baseUrl = rtrim(
        (string) config('services.supplier_service.base_url'),
        '/'
    );

    if ($baseUrl === '') {
        Log::warning(
            'URL du microservice Supplier non configurée.'
        );

        return [];
    }

    try {
        $response = Http::acceptJson()
            ->timeout(5)
            ->post($baseUrl . '/batch', [
                'ids' => $supplierIds,
            ]);

        if (!$response->successful()) {
            Log::warning(
                'Impossible de récupérer les fournisseurs en batch.',
                [
                    'supplier_ids' => $supplierIds,
                    'http_status' => $response->status(),
                ]
            );

            return [];
        }

        $suppliers = $response->json('data');

        if (!is_array($suppliers)) {
            return [];
        }

        $indexedSuppliers = [];

        foreach ($suppliers as $supplier) {
            if (!is_array($supplier)) {
                continue;
            }

            if (!isset($supplier['id'])) {
                continue;
            }

            $supplierId = (int) $supplier['id'];

            $indexedSuppliers[$supplierId] = [
                'id' => $supplierId,
                'name' => $supplier['name'] ?? null,
                'dueDate' => $supplier['dueDate'] ?? null,
            ];
        }

        return $indexedSuppliers;
    } catch (Throwable $e) {
        Log::warning(
            'Erreur lors de la récupération des fournisseurs en batch.',
            [
                'supplier_ids' => $supplierIds,
                'message' => $e->getMessage(),
            ]
        );

        return [];
    }
}


    /**
     * Récupère les informations d'un fournisseur depuis le microservice Supplier.
     *
     * En cas d'indisponibilité du service, retourne null afin de ne pas
     * bloquer la génération du résumé financier.
     *
     * @param int|null $supplierId
     * @return array|null
     */
    public function getSupplier(?int $supplierId): ?array
    {
        if (!$supplierId || $supplierId <= 0) {
            return null;
        }

        $baseUrl = rtrim(
            (string) config('services.supplier_service.base_url'),
            '/'
        );

        if ($baseUrl === '') {
            Log::warning(
                'URL du microservice Supplier non configurée.'
            );

            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(3)
                ->get($baseUrl . '/' . $supplierId);

            if (!$response->successful()) {
                Log::warning(
                    'Impossible de récupérer le fournisseur.',
                    [
                        'supplier_id' => $supplierId,
                        'http_status' => $response->status(),
                    ]
                );

                return null;
            }

            $supplier = $response->json('data');

            if (!is_array($supplier)) {
                return null;
            }

            return [
                'id' => isset($supplier['id'])
                    ? (int) $supplier['id']
                    : $supplierId,

                'name' => $supplier['name'] ?? null,

                'dueDate' => $supplier['dueDate'] ?? null,
            ];
        } catch (Throwable $e) {
            Log::warning(
                'Erreur lors de la communication avec le microservice Supplier.',
                [
                    'supplier_id' => $supplierId,
                    'message' => $e->getMessage(),
                ]
            );

            return null;
        }
    }
}