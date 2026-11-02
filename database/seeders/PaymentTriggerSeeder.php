<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTriggerSeeder extends Seeder
{
    public function run()
    {
        $triggers = [
            [
                'code' => 'ORDER_APPROVAL',
                'label' => 'À l’approbation de la commande',
                'sequence' => 1,
            ],
            [
                'code' => 'ORDER_SIGNATURE',
                'label' => 'À la signature du bon de commande',
                'sequence' => 2,
            ],
            [
                'code' => 'ADVANCE_PAYMENT',
                'label' => 'Au versement de l’acompte',
                'sequence' => 3,
            ],
            [
                'code' => 'DELIVERY',
                'label' => 'À la livraison',
                'sequence' => 4,
            ],
            [
                'code' => 'PARTIAL_DELIVERY',
                'label' => 'À chaque livraison partielle',
                'sequence' => 5,
            ],
            [
                'code' => 'RECEPTION',
                'label' => 'À la réception du matériel',
                'sequence' => 6,
            ],
            [
                'code' => 'INSTALLATION',
                'label' => 'Après installation',
                'sequence' => 7,
            ],
            [
                'code' => 'SERVICE_COMPLETION',
                'label' => 'À la fin de la prestation',
                'sequence' => 8,
            ],
            [
                'code' => 'ACCEPTANCE',
                'label' => 'Après validation de la réception',
                'sequence' => 9,
            ],
            [
                'code' => 'INVOICE_RECEIPT',
                'label' => 'À réception de la facture',
                'sequence' => 10,
            ],
            [
                'code' => 'FIXED_DELAY',
                'label' => 'Après un délai défini',
                'sequence' => 11,
            ],
        ];

        foreach ($triggers as $trigger) {
            DB::table('payment_triggers')->updateOrInsert(
                ['code' => $trigger['code']],
                array_merge($trigger, [
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}