<?php

namespace App\Services;

use Carbon\Carbon;

class DocumentDataMapper
{
    /**
     * Retourne les données prêtes à être injectées dans le document enfant
     * selon le type de document.
     *
     * @param string $type
     * @param array $validated
     * @return array
     */
    public function map(string $type, array $validated): array
    {
        $map = [
            'invoice' => [
                'amount' => fn($v) => $v['montant'] ?? null,
                'provider' => fn($v) => $v['prestataire'] ?? null,
                'provider_reference' => fn($v) => $v['reference_fournisseur'] ?? null,
                'deposit_date' => fn($v) => isset($v['dateDepot']) ? Carbon::parse($v['dateDepot'])->format('Y-m-d H:i:s') : null,
            ],
            'taxi' => [
                'amount' => fn($v) => $v['montant'] ?? null,
                'employee_id' => fn($v) => $v['employee_id'] ?? null,
                'date' => fn($v) => isset($v['dateDepot']) ? Carbon::parse($v['dateDepot'])->format('Y-m-d') : null,
                'description' => fn($v) => $v['description'] ?? null,
            ],
            // Ajoute d’autres types ici
        ];

        if (!isset($map[$type])) {
            throw new \Exception("Mapper introuvable pour le type de document {$type}");
        }

        $data = [];
        foreach ($map[$type] as $field => $callback) {
            $data[$field] = $callback($validated);
        }

        return $data;
    }
}
