<?php

namespace App\Services;


class DocumentStatusUIMapper
{
    public function map($status)
    {
        switch ($status) {

            case 'ADVANCE_IN_PROGRESS':
                return [
                    'label' => 'Avance en cours',
                    'color' => 'orange',
                    'emoji' => '💰'
                ];

            case 'PAYMENT_IN_PROGRESS':
                return [
                    'label' => 'Paiement en cours',
                    'color' => 'blue',
                    'emoji' => '💳'
                ];

            case 'TO_BE_SETTLED':
                return [
                    'label' => 'À régulariser',
                    'color' => 'red',
                    'emoji' => '⚠️'
                ];

            case 'CLOSED':
                return [
                    'label' => 'Clôturé',
                    'color' => 'green',
                    'emoji' => '✔️'
                ];

            default:
                return [
                    'label' => 'Inconnu',
                    'color' => 'gray',
                    'emoji' => '❓'
                ];
        }
    }
}