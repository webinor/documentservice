<?php

namespace App\Exports\Mission;

use Maatwebsite\Excel\Concerns\FromArray;

class MissionAdvancesSheet implements FromArray
{
    private $advances;

    public function __construct(array $advances)
    {
        $this->advances = $advances;//['items'] ?? [];
    }

    public function array(): array
    {
        $data = [];

        // =========================
        // SUMMARY
        // =========================
        $data[] = ['--- RESUME AVANCES ---'];
        $data[] = ['TOTAL', $this->advances['total'] ?? 0];
        $data[] = ['PAYEES', $this->advances['paid']['total'] ?? 0];
        $data[] = ['EN ATTENTE', $this->advances['pending']['total'] ?? 0];
        $data[] = ['ANNULEES', $this->advances['cancelled']['total'] ?? 0];

        $data[] = ['']; // ligne vide

        // =========================
        // DETAILS
        // =========================
        $data[] = [
            'Montant',
            'Date paiement',
            'Statut',
            'Reference',
        ];

        foreach ($this->advances['items'] ?? [] as $item) {

            $data[] = [
                $item['amount'] ?? 0,
                $item['payment_date'] ?? null,
                $item['status'] ?? null,
                $item['reference'] ?? null,
            ];
        }

        return $data;
    }
}