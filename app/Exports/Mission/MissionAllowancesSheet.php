<?php

namespace App\Exports\Mission;

use Maatwebsite\Excel\Concerns\FromArray;

class MissionAllowancesSheet implements FromArray
{
    private $allowances;

    public function __construct(array $allowances)
    {
        $this->allowances = $allowances['items'] ?? [];
    }

    public function array(): array
    {
        $data = [];

        // HEADER
        $data[] = [
            'Nom',
            'Code',
            'Type (PREVU / REEL)',
            'Quantite',
            'Montant unitaire',
            'Total',
            'Stage',
        ];

        foreach ($this->allowances as $item) {

            /**
             * NORMALISATION DU TYPE FINANCIER
             */
            $type = $item['is_estimated']
                ? 'PREVU'
                : 'REEL';

            /**
             * DONNEES SAFE (robustesse Excel)
             */
            $data[] = [
                $item['name'] ?? 'N/A',
                $item['code'] ?? null,
                $type,
                $item['quantity'] ?? 0,
                $item['unit_amount'] ?? 0,
                $item['total'] ?? 0,
                $item['calculation_stage'] ?? null,
            ];
        }

        return $data;
    }
}