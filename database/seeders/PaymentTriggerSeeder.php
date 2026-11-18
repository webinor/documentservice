<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTriggerSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {

            $triggers = [

                /*
                 * =====================================================
                 * PAIEMENTS À LA COMMANDE
                 * =====================================================
                 */

                [
                    'code' => 'ORDER_APPROVAL_PARTIAL',
                    'label' => 'Paiement partiel à l’approbation de la commande',
                    'sequence' => 1,
                    'is_initial_payment' => true,
                ],
                [
                    'code' => 'ORDER_APPROVAL_FULL',
                    'label' => 'Paiement total à l’approbation de la commande',
                    'sequence' => 2,
                    'is_initial_payment' => true,
                ],
                [
                    'code' => 'ORDER_SIGNATURE_PARTIAL',
                    'label' => 'Paiement partiel à la signature du bon de commande',
                    'sequence' => 3,
                    'is_initial_payment' => true,
                ],
                [
                    'code' => 'ORDER_SIGNATURE_FULL',
                    'label' => 'Paiement total à la signature du bon de commande',
                    'sequence' => 4,
                    'is_initial_payment' => true,
                ],

                /*
                 * =====================================================
                 * PAIEMENTS APRÈS COMMANDE
                 * =====================================================
                 */

                [
                    'code' => 'DELIVERY',
                    'label' => 'Paiement total à la livraison',
                    'sequence' => 5,
                    'is_initial_payment' => false,
                ],
                [
                    'code' => 'PARTIAL_DELIVERY',
                    'label' => 'Paiement partiel à chaque livraison partielle',
                    'sequence' => 6,
                    'is_initial_payment' => false,
                ],
                [
                    'code' => 'RECEPTION',
                    'label' => 'Paiement total à la réception du matériel',
                    'sequence' => 7,
                    'is_initial_payment' => false,
                ],
                [
                    'code' => 'INSTALLATION',
                    'label' => 'Paiement total après installation',
                    'sequence' => 8,
                    'is_initial_payment' => false,
                ],
                [
                    'code' => 'SERVICE_COMPLETION',
                    'label' => 'Paiement total à la fin de la prestation',
                    'sequence' => 9,
                    'is_initial_payment' => false,
                ],
                [
                    'code' => 'ACCEPTANCE',
                    'label' => 'Paiement après validation de la réception',
                    'sequence' => 10,
                    'is_initial_payment' => false,
                ],
                [
                    'code' => 'INVOICE_RECEIPT',
                    'label' => 'Paiement à réception de la facture',
                    'sequence' => 11,
                    'is_initial_payment' => false,
                ],
                [
                    'code' => 'FIXED_DELAY',
                    'label' => 'Paiement après un délai défini',
                    'sequence' => 12,
                    'is_initial_payment' => false,
                ],
            ];

            /*
             * =====================================================
             * MODES DE PAIEMENT AUTORISÉS PAR TRIGGER
             * =====================================================
             *
             * DIRECT_SUPPLIER : paiement directement au fournisseur.
             * ASSIGNED_AGENT  : paiement effectué par un agent désigné.
             *
             * Les paiements initiaux autorisent les deux modes.
             * Les autres triggers autorisent uniquement le paiement
             * direct au fournisseur.
             */

            $paymentModesByTrigger = [

                'ORDER_APPROVAL_PARTIAL' => [
                    'DIRECT_SUPPLIER',
                ],

                'ORDER_APPROVAL_FULL' => [
                    'DIRECT_SUPPLIER',
                    'ASSIGNED_AGENT',
                ],

                'ORDER_SIGNATURE_PARTIAL' => [
                    'DIRECT_SUPPLIER',
                ],

                'ORDER_SIGNATURE_FULL' => [
                    'DIRECT_SUPPLIER',
                    'ASSIGNED_AGENT',
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

                $paymentTrigger = DB::table('payment_triggers')
                    ->where('code', $trigger['code'])
                    ->first();

                if (!$paymentTrigger) {
                    continue;
                }

                $allowedModes = isset(
                    $paymentModesByTrigger[$trigger['code']]
                )
                    ? $paymentModesByTrigger[$trigger['code']]
                    : ['DIRECT_SUPPLIER'];

                // Synchronisation des modes autorisés.
                DB::table('payment_trigger_modes')
                    ->where(
                        'payment_trigger_id',
                        $paymentTrigger->id
                    )
                    ->delete();

                $now = now();

                $records = [];

                foreach ($allowedModes as $mode) {
                    $records[] = [
                        'payment_trigger_id' => $paymentTrigger->id,
                        'payment_mode' => $mode,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($records)) {
                    DB::table('payment_trigger_modes')->insert($records);
                }
            }
        });
    }
}