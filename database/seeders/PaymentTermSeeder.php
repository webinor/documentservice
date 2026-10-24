<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentTerm;
use App\Models\PaymentTermInstallment;

class PaymentTermSeeder extends Seeder
{
    /**
     * Crée les conditions de paiement de test et leurs échéances.
     *
     * Le seeder peut être exécuté plusieurs fois sans créer de doublons.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {

            $paymentTerms = [
                [
                    'code' => 'CASH',
                    'name' => 'Paiement comptant',
                    'description' => 'Paiement intégral à la commande.',
                    'payment_mode' => 'IMMEDIATE',
                    'due_days' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'installments' => [
                        [
                            'sequence' => 1,
                            'label' => 'Paiement intégral à la commande',
                            'trigger_type' => 'ORDER_DATE',
                            'days_after_trigger' => 0,
                            'percentage' => 100,
                        ],
                    ],
                ],

                [
                    'code' => 'NET_15',
                    'name' => 'Paiement à 15 jours',
                    'description' => 'Paiement intégral 15 jours après réception de la facture.',
                    'payment_mode' => 'NET_DAYS',
                    'due_days' => 15,
                    'is_default' => false,
                    'is_active' => true,
                    'installments' => [
                        [
                            'sequence' => 1,
                            'label' => 'Règlement à 15 jours',
                            'trigger_type' => 'INVOICE_RECEIVED',
                            'days_after_trigger' => 15,
                            'percentage' => 100,
                        ],
                    ],
                ],

                [
                    'code' => 'NET_30',
                    'name' => 'Paiement à 30 jours',
                    'description' => 'Paiement intégral 30 jours après réception de la facture.',
                    'payment_mode' => 'NET_DAYS',
                    'due_days' => 30,
                    'is_default' => false,
                    'is_active' => true,
                    'installments' => [
                        [
                            'sequence' => 1,
                            'label' => 'Règlement à 30 jours',
                            'trigger_type' => 'INVOICE_RECEIVED',
                            'days_after_trigger' => 30,
                            'percentage' => 100,
                        ],
                    ],
                ],

                [
                    'code' => 'HALF_HALF',
                    'name' => '50 % à la commande, 50 % à la livraison',
                    'description' => 'Un acompte à la commande et le solde à la livraison.',
                    'payment_mode' => 'INSTALLMENTS',
                    'due_days' => null,
                    'is_default' => false,
                    'is_active' => true,
                    'installments' => [
                        [
                            'sequence' => 1,
                            'label' => 'Acompte à la commande',
                            'trigger_type' => 'ORDER_DATE',
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
                    'code' => 'THREE_STEPS',
                    'name' => 'Paiement en trois tranches',
                    'description' => '30 % à la commande, 40 % à la livraison et 30 % après réception conforme.',
                    'payment_mode' => 'INSTALLMENTS',
                    'due_days' => null,
                    'is_default' => false,
                    'is_active' => true,
                    'installments' => [
                        [
                            'sequence' => 1,
                            'label' => 'Acompte à la commande',
                            'trigger_type' => 'ORDER_DATE',
                            'days_after_trigger' => 0,
                            'percentage' => 30,
                        ],
                        [
                            'sequence' => 2,
                            'label' => 'Versement à la livraison',
                            'trigger_type' => 'DELIVERY',
                            'days_after_trigger' => 0,
                            'percentage' => 40,
                        ],
                        [
                            'sequence' => 3,
                            'label' => 'Solde après réception conforme',
                            'trigger_type' => 'ACCEPTANCE',
                            'days_after_trigger' => 0,
                            'percentage' => 30,
                        ],
                    ],
                ],

                [
                    'code' => 'NET_45',
                    'name' => 'Paiement à 45 jours',
                    'description' => 'Paiement intégral 45 jours après réception de la facture.',
                    'payment_mode' => 'NET_DAYS',
                    'due_days' => 45,
                    'is_default' => false,
                    'is_active' => true,
                    'installments' => [
                        [
                            'sequence' => 1,
                            'label' => 'Règlement à 45 jours',
                            'trigger_type' => 'INVOICE_RECEIVED',
                            'days_after_trigger' => 45,
                            'percentage' => 100,
                        ],
                    ],
                ],
            ];

            /*
             * Réinitialise le statut par défaut pour les données de test.
             * Une seule condition sera définie comme condition par défaut.
             */
            PaymentTerm::query()->update([
                'is_default' => false,
            ]);

            foreach ($paymentTerms as $termData) {

                $installments = $termData['installments'];

                unset($termData['installments']);

                /*
                 * Crée la condition ou actualise celle qui existe déjà.
                 */
                $paymentTerm = PaymentTerm::updateOrCreate(
                    [
                        'code' => $termData['code'],
                    ],
                    $termData
                );

                /*
                 * Actualise les échéances définies dans le seeder.
                 */
                foreach ($installments as $installmentData) {

                    PaymentTermInstallment::updateOrCreate(
                        [
                            'payment_term_id' => $paymentTerm->id,
                            'sequence' => $installmentData['sequence'],
                        ],
                        $installmentData
                    );
                }

                /*
                 * Supprime les anciennes échéances qui ne figurent plus
                 * dans la configuration du seeder.
                 */
                $sequences = array_column($installments, 'sequence');

                PaymentTermInstallment::where(
                    'payment_term_id',
                    $paymentTerm->id
                )
                    ->whereNotIn('sequence', $sequences)
                    ->delete();
            }
        });
    }
}