<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentStatus;

class DocumentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [

            /*
            |--------------------------------------------------------------------------
            | GENERIQUES
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'DRAFT',
                'label' => 'Brouillon',
                'description' => 'Document en cours de création',
                'color' => 'secondary',
                'category' => 'INITIAL',
                'is_final' => false,
                'triggers_reminder' => false,
            ],

            [
                'code' => 'SUBMITTED',
                'label' => 'Soumis',
                'description' => 'Document soumis pour traitement',
                'color' => 'info',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => false,
            ],

            [
                'code' => 'REJECTED',
                'label' => 'Rejeté',
                'description' => 'Document rejeté',
                'color' => 'danger',
                'category' => 'FINAL',
                'is_final' => true,
                'triggers_reminder' => false,
            ],

            [
                'code' => 'CANCELLED',
                'label' => 'Annulé',
                'description' => 'Document annulé',
                'color' => 'dark',
                'category' => 'FINAL',
                'is_final' => true,
                'triggers_reminder' => false,
            ],

            [
                'code' => 'CLOSED',
                'label' => 'Clôturé',
                'description' => 'Document clôturé',
                'color' => 'success',
                'category' => 'FINAL',
                'is_final' => true,
                'triggers_reminder' => false,
            ],

            /*
            |--------------------------------------------------------------------------
            | VALIDATIONS
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'PENDING_MANAGER_APPROVAL',
                'label' => 'En attente validation manager',
                'description' => 'Validation du responsable hiérarchique en attente',
                'color' => 'warning',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => true,
            ],

            [
                'code' => 'PENDING_FINANCE_APPROVAL',
                'label' => 'En attente validation finance',
                'description' => 'Validation finance en attente',
                'color' => 'warning',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => true,
            ],

            [
                'code' => 'PENDING_DAF_APPROVAL',
                'label' => 'En attente validation DAF',
                'description' => 'Validation DAF en attente',
                'color' => 'warning',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | CAISSE / PAIEMENT
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'WAITING_DISBURSEMENT',
                'label' => 'En attente décaissement',
                'description' => 'En attente de décaissement caisse',
                'color' => 'primary',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => true,
            ],

            [
                'code' => 'WAITING_SIGNATURE',
                'label' => 'En attente signature',
                'description' => 'En attente de signature du bénéficiaire',
                'color' => 'primary',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => true,
            ],

            [
                'code' => 'DISBURSED',
                'label' => 'Décaissé',
                'description' => 'Paiement effectué',
                'color' => 'success',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => false,
            ],

            /*
            |--------------------------------------------------------------------------
            | MISSIONS
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'IN_PROGRESS',
                'label' => 'Mission en cours',
                'description' => 'Mission en cours d’exécution',
                'color' => 'info',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => false,
            ],

            [
                'code' => 'PENDING_REPORT',
                'label' => 'En attente rapport',
                'description' => 'Rapport mission attendu',
                'color' => 'warning',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => true,
            ],

            [
                'code' => 'PENDING_REGULATION',
                'label' => 'En attente régularisation',
                'description' => 'Mission en attente de régularisation financière',
                'color' => 'danger',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | CONTRATS
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'PENDING_SIGNATURE_APPROVAL',
                'label' => 'En attente signature',
                'description' => 'Contrat en attente de signature',
                'color' => 'warning',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => true,
            ],

            [
                'code' => 'ACTIVE',
                'label' => 'Actif',
                'description' => 'Contrat actif',
                'color' => 'success',
                'category' => 'IN_PROGRESS',
                'is_final' => false,
                'triggers_reminder' => false,
            ],

            [
                'code' => 'TERMINATED',
                'label' => 'Terminé',
                'description' => 'Contrat terminé',
                'color' => 'dark',
                'category' => 'FINAL',
                'is_final' => true,
                'triggers_reminder' => false,
            ],

        ];

        foreach ($statuses as $status) {

            DocumentStatus::updateOrCreate(
                [
                    'code' => $status['code']
                ],
                $status
            );
        }
    }
}