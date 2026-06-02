<?php

namespace App\Exports\Mission;

use Maatwebsite\Excel\Concerns\FromArray;

class MissionRegulationsSheet implements FromArray
{
    private $regulations;

    public function __construct(array $regulations)
    {
        $this->regulations =
            $regulations['items'] ?? [];
    }

    public function array(): array
    {
        $data = [];

        $data[] = [
            'Type',
            'Montant',
            'Statut',
            'Méthode',
            'Référence',
            'Solde avant',
            'Solde après',
            'Date traitement',
        ];

        foreach ($this->regulations as $item) {

            $data[] = [
                strtoupper($item['type'] ?? ''),

                $item['amount'] ?? 0,

                strtoupper($item['status'] ?? ''),

                $item['payment_method'] ?? '',

                $item['reference'] ?? '',

                $item['balance_before'] ?? 0,

                $item['balance_after'] ?? 0,

                $item['processed_at'] ?? '',
            ];
        }

        return $data;
    }
}