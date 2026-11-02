<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTermSeeder extends Seeder
{
    /**
     * Modèles de conditions de paiement.
     *
     * Chaque modèle contient ses propres échéances.
     *
     * @return void
     */
    public function run()
    {
        $templates = [
            [
                'code' => 'PAYMENT_30_70',
                'name' => '30 % à la commande - 70 % à la réception',
                'description' => 'Avance de 30 % à la commande et solde de 70 % à la réception.',
                'payment_mode' => 'PERCENTAGE',
                'installments' => [
                    [
                        'sequence' => 1,
                        'label' => 'Avance à la commande',
                        'trigger_type' => 'ORDER_SIGNATURE',
                        'days_after_trigger' => 0,
                        'percentage' => 30,
                    ],
                    [
                        'sequence' => 2,
                        'label' => 'Solde à la réception',
                        'trigger_type' => 'RECEPTION',
                        'days_after_trigger' => 0,
                        'percentage' => 70,
                    ],
                ],
            ],

            [
                'code' => 'PAYMENT_50_50',
                'name' => '50 % à la commande - 50 % à la livraison',
                'description' => 'Paiement en deux tranches égales.',
                'payment_mode' => 'PERCENTAGE',
                'installments' => [
                    [
                        'sequence' => 1,
                        'label' => 'Première tranche',
                        'trigger_type' => 'ORDER_SIGNATURE',
                        'days_after_trigger' => 0,
                        'percentage' => 50,
                    ],
                    [
                        'sequence' => 2,
                        'label' => 'Solde à la livraison',
                        'trigger_type' => 'DELIVERY',
                        'days_after_trigger' => 0,
                        'percentage' => 50,
                    ],
                ],
            ],

            [
                'code' => 'PAYMENT_40_40_20',
                'name' => '40 % - 40 % - 20 %',
                'description' => 'Paiement en trois étapes : commande, livraison et réception.',
                'payment_mode' => 'PERCENTAGE',
                'installments' => [
                    [
                        'sequence' => 1,
                        'label' => 'Avance à la commande',
                        'trigger_type' => 'ORDER_SIGNATURE',
                        'days_after_trigger' => 0,
                        'percentage' => 40,
                    ],
                    [
                        'sequence' => 2,
                        'label' => 'Paiement à la livraison',
                        'trigger_type' => 'DELIVERY',
                        'days_after_trigger' => 0,
                        'percentage' => 40,
                    ],
                    [
                        'sequence' => 3,
                        'label' => 'Solde après réception',
                        'trigger_type' => 'RECEPTION',
                        'days_after_trigger' => 0,
                        'percentage' => 20,
                    ],
                ],
            ],

            [
                'code' => 'PAYMENT_100_RECEPTION',
                'name' => '100 % à la réception',
                'description' => 'Paiement intégral après réception des biens ou services.',
                'payment_mode' => 'PERCENTAGE',
                'installments' => [
                    [
                        'sequence' => 1,
                        'label' => 'Paiement intégral à la réception',
                        'trigger_type' => 'RECEPTION',
                        'days_after_trigger' => 0,
                        'percentage' => 100,
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($templates) {

            foreach ($templates as $template) {

                /*
                 * Recherche du modèle existant.
                 */
                $paymentTerm = DB::table('payment_terms')
                    ->where('code', $template['code'])
                    ->first();

                /*
                 * Données du modèle.
                 */
                $paymentTermData = [
                    'code' => $template['code'],
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'payment_mode' => $template['payment_mode'],
                    'is_template' => true,
                    'is_active' => true,
                    'updated_at' => now(),
                ];

                /*
                 * Mise à jour ou création du modèle.
                 */
                if ($paymentTerm) {

                    DB::table('payment_terms')
                        ->where('id', $paymentTerm->id)
                        ->update($paymentTermData);

                    $paymentTermId = $paymentTerm->id;

                } else {

                    $paymentTermId = DB::table('payment_terms')
                        ->insertGetId(array_merge(
                            $paymentTermData,
                            [
                                'created_at' => now(),
                            ]
                        ));
                }

                /*
                 * Synchronisation des échéances.
                 */
                foreach ($template['installments'] as $installment) {

                    DB::table('payment_term_installments')
                        ->updateOrInsert(
                            [
                                'payment_term_id' => $paymentTermId,
                                'sequence' => $installment['sequence'],
                            ],
                            [
                                'label' => $installment['label'],
                                'trigger_type' => $installment['trigger_type'],
                                'days_after_trigger' => $installment['days_after_trigger'],
                                'percentage' => $installment['percentage'],
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                }

                /*
                 * Suppression des anciennes échéances qui ne figurent
                 * plus dans la configuration du modèle.
                 */
                $sequences = array_column(
                    $template['installments'],
                    'sequence'
                );

                DB::table('payment_term_installments')
                    ->where('payment_term_id', $paymentTermId)
                    ->whereNotIn('sequence', $sequences)
                    ->delete();
            }
        });
    }
}