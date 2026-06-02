<?php

namespace App\Exports\Mission;

use Maatwebsite\Excel\Concerns\FromArray;

class MissionExpensesSheet implements FromArray
{
    private $expenses;

    public function __construct(array $expenses)
    {
        $this->expenses = $expenses['expenses'] ?? [];
    }

    public function array(): array
    {
        $data = [];

        // HEADER (important pour Excel)
        $data[] = [
            'Categorie',
            'Type',
            'Quantite',
            'Montant unitaire',
            'Total',
            'Stage',
        ];

        foreach ($this->expenses as $expense) {

            $categoryName =
                $expense['expense_category']['name'] ?? 'N/A';

            $type = $expense['type'] ?? '';

            /**
             * Gestion PREVISIONNELLE vs DECLAREE
             */
            if ($type === 'PREVISIONNELLE') {
                $quantity = $expense['planned_quantity'] ?? 0;
                $total = $expense['planned_total'] ?? 0;
            } else {
                $quantity = $expense['final_quantity'] ?? 0;
                $total = $expense['final_total'] ?? 0;
            }

            $data[] = [
                $categoryName,
                $type,
                $quantity,
                $expense['amount'] ?? 0,
                $total,
                $expense['calculation_stage'] ?? null,
            ];
        }

        return $data;
    }
}