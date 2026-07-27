<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentReferenceTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [

            [
                'code' => 'ACCOUNTING_ENTRY',
                'label' => 'Numéro de Pièce comptable',
                'description' => 'Référence de la pièce comptable.',
                'has_attachment' => false,
                'allow_multiple' => false,
            ],

            [
                'code' => 'PAYMENT_CERTIFICATE',
                'label' => 'Attestation de règlement',
                'description' => 'Attestation de règlement.',
                'has_attachment' => true,
                'allow_multiple' => true,
            ],

            [
                'code' => 'PAYMENT_PROOF',
                'label' => 'Preuve de paiement',
                'description' => 'Justificatif ou preuve de paiement.',
                'has_attachment' => true,
                'allow_multiple' => true,
            ],

            [
                'code' => 'BANK_TRANSFER',
                'label' => 'Avis de virement',
                'description' => 'Avis ou justificatif de virement bancaire.',
                'has_attachment' => true,
                'allow_multiple' => true,
            ],

            [
                'code' => 'CHECK',
                'label' => 'Chèque',
                'description' => 'Référence d’un chèque.',
                'has_attachment' => true,
                'allow_multiple' => true,
            ],

            [
                'code' => 'PURCHASE_ORDER',
                'label' => 'Bon de commande',
                'description' => 'Référence du bon de commande.',
                'has_attachment' => true,
                'allow_multiple' => false,
            ],

            [
                'code' => 'ERP_REFERENCE',
                'label' => 'Référence ERP',
                'description' => 'Référence provenant d’un ERP.',
                'has_attachment' => false,
                'allow_multiple' => true,
            ],

            [
                'code' => 'OTHER',
                'label' => 'Autre',
                'description' => 'Autre type de référence documentaire.',
                'has_attachment' => true,
                'allow_multiple' => true,
            ],

        ];

        foreach ($types as $type) {

            DB::table('document_reference_types')->updateOrInsert(
                [
                    'code' => $type['code'],
                ],
                [
                    'label' => $type['label'],
                    'description' => $type['description'],
                    'has_attachment' => $type['has_attachment'],
                    'allow_multiple' => $type['allow_multiple'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}