<?php

use Illuminate\Support\Facades\Http;

if (! function_exists('getSupplierInfo')) {
    /**
     * Récupère les informations d'un fournisseur via le microservice supplier_service.
     *
     * @param int|string $providerId
     * @return array|null
     */
    function getSupplierInfo($providerId): ?array
    {
        if (!$providerId) {
            return null;
        }

        try {
            $response = Http::withToken(config('services.supplier_service.token'))
                ->acceptJson()
                ->get(config('services.supplier_service.base_url') . "/{$providerId}");

            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }
}