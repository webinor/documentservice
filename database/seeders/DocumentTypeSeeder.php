<?php

namespace Database\Seeders;

use App\Models\Misc\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run()
    {
        $documentTypes = [

            /*
            |--------------------------------------------------------------------------
            | FACTURE FOURNISSEUR / PRESTATAIRE MEDICAL
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'NC5Fz0yDlH',
                'name' => 'Facture Fournisseur/Prestataire Medical',
                'slug' => 'facture-fournisseur-medical',
                'class_name' => 'App\\Models\\Finance\\InvoiceProvider.App\\Models\\MedicalSupplier',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'invoice_provider.medical_supplier',
                'icon' => null,
                'color' => 'blue',
                'dashboard_title' => null,
                'dashboard_subtitle' => null,
                'view_route' => null,
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | FACTURE FOURNISSEUR / PRESTATAIRE INFORMATIQUE
            |--------------------------------------------------------------------------
            */

            [
                'code' => '7wUF5GPBSV',
                'name' => 'Facture Fournisseur/Prestataire Informatique',
                'slug' => 'facture-fournisseur-informatique',
                'class_name' => 'App\\Models\\Finance\\InvoiceProvider.App\\Models\\ItSupplier',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'invoice_provider.it_supplier',
                'icon' => '🧾',
                'color' => 'purple',
                'dashboard_title' => 'Factures',
                'dashboard_subtitle' => 'À contrôler',
                'view_route' => '/workflow/documents/facture-fournisseur-informatique',
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | FACTURE NOTE HONORAIRE
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'z3dZQvmFbG',
                'name' => 'Facture Note honoraire',
                'slug' => 'facture-note-honoraire',
                'class_name' => 'App\\Models\\Finance\\FeeNote',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'fee_note',
                'icon' => null,
                'color' => 'blue',
                'dashboard_title' => null,
                'dashboard_subtitle' => null,
                'view_route' => null,
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | OLD DEMANDE ACHAT
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'W2nsXHwSIH',
                'name' => 'Old Demande Achat',
                'slug' => 'old-demande-achat',
                'class_name' => 'App\\Models\\RequestOrder',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'request_order',
                'icon' => null,
                'color' => 'blue',
                'dashboard_title' => null,
                'dashboard_subtitle' => null,
                'view_route' => null,
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | COURRIER IMPOTS
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'zVB70ssYxM',
                'name' => 'Courrier Impots',
                'slug' => 'courrier-impots',
                'class_name' => 'App\\Models\\Tax',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'tax',
                'icon' => null,
                'color' => 'blue',
                'dashboard_title' => null,
                'dashboard_subtitle' => null,
                'view_route' => null,
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'AUTO_BY_ROLE',
            ],

            /*
            |--------------------------------------------------------------------------
            | COURRIER MISES EN DEMEURE
            |--------------------------------------------------------------------------
            */

            [
                'code' => '1p1fMdYiGZ',
                'name' => 'Courrier Mises en demeure',
                'slug' => 'courrier-mises-en-demeure',
                'class_name' => 'App\\Models\\FormalNotice',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'formal_notice',
                'icon' => null,
                'color' => 'blue',
                'dashboard_title' => null,
                'dashboard_subtitle' => null,
                'view_route' => null,
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'AUTO_BY_ROLE',
            ],

            /*
            |--------------------------------------------------------------------------
            | CONTRATS
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'loDJCCGHq6',
                'name' => 'Contrats',
                'slug' => 'contrats',
                'class_name' => 'App\\Models\\contract',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'contracts',
                'icon' => null,
                'color' => 'blue',
                'dashboard_title' => null,
                'dashboard_subtitle' => null,
                'view_route' => null,
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'AUTO_BY_ROLE',
            ],

            /*
            |--------------------------------------------------------------------------
            | PAPIER TAXI
            |--------------------------------------------------------------------------
            */

            [
                'code' => '2344rf',
                'name' => 'Papier Taxi',
                'slug' => 'papier-taxi',
                'class_name' => 'App\\Models\\TaxiPaper',
                'creation_handler_class' => 'App\\Services\\TaxiPaper\\TaxiPaperDocumentHandler',
                'enrichment_handler_class' => 'App\\Services\\TaxiPaper\\TaxiPaperDocumentEnrichmentHandler',
                'relation_name' => 'taxi_paper',
                'icon' => '🚕',
                'color' => 'orange',
                'dashboard_title' => 'Papiers Taxi',
                'dashboard_subtitle' => 'À valider',
                'view_route' => '/papiers-taxi-a-valider',
                'view_own_route' => '/mes-papiers-taxi',
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | NOTE DE FRAIS
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'zsdfwe89',
                'name' => 'Note de Frais',
                'slug' => 'note-de-frais',
                'class_name' => 'App\\Models\\FeeNote',
                'creation_handler_class' => 'App\\Services\\FeeNote\\FeeNoteDocumentHandler',
                'enrichment_handler_class' => 'App\\Services\\FeeNote\\FeeNoteDocumentEnrichmentHandler',
                'relation_name' => 'fee_note',
                'icon' => '🧾',
                'color' => 'purple',
                'dashboard_title' => 'Notes de frais',
                'dashboard_subtitle' => 'À valider',
                'view_route' => '/notes-de-frais-a-valider',
                'view_own_route' => '/mes-notes-de-frais',
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | DEMANDE D'ABSENCE
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'asrgsef',
                'name' => "Demande D'absence",
                'slug' => 'demande-d-absence',
                'class_name' => 'App\\Models\\AbsenceRequest',
                'creation_handler_class' => 'App\\Services\\Absence\\AbsenceDocumentHandler',
                'enrichment_handler_class' => 'App\\Services\\Absence\\AbsenceDocumentEnrichmentHandler',
                'relation_name' => 'absence_request',
                'icon' => '🏖️',
                'color' => 'blue',
                'dashboard_title' => "Demandes d'absence",
                'dashboard_subtitle' => 'À valider',
                'view_route' => '/demandes-absence-a-valider',
                'view_own_route' => '/mes-demandes-absence',
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | NOTE DE SERVICE
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'qefwac',
                'name' => 'Note de service',
                'slug' => 'note-de-service',
                'class_name' => 'App\\Models\\Note',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'note_service',
                'icon' => null,
                'color' => 'blue',
                'dashboard_title' => null,
                'dashboard_subtitle' => null,
                'view_route' => null,
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'AUTO_BY_ROLE',
            ],

            /*
            |--------------------------------------------------------------------------
            | MISSION
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'MISSION',
                'name' => 'Mission',
                'slug' => 'mission',
                'class_name' => 'App\\Models\\Mission',
                'creation_handler_class' => 'App\\Services\\Mission\\MissionDocumentHandler',
                'enrichment_handler_class' => 'App\\Services\\Mission\\MissionDocumentEnrichmentHandler',
                'relation_name' => 'mission',
                'icon' => '📄',
                'color' => 'blue',
                'dashboard_title' => 'Mission',
                'dashboard_subtitle' => 'À valider',
                'view_route' => '/missions-a-valider',
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACHAT
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'PURCHASE_REQUEST',
                'name' => 'Achat',
                'slug' => 'achat',
                'class_name' => 'App\\Models\\PurchaseRequest',
                'creation_handler_class' => null,
                'enrichment_handler_class' => null,
                'relation_name' => 'purchase_request',
                'icon' => '📄',
                'color' => 'blue',
                'dashboard_title' => 'Achats',
                'dashboard_subtitle' => 'À valider',
                'view_route' => '/achats-a-valider',
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | FICHE À RÉGULARISER
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'REGULARIZATION_SHEET',
                'name' => 'Fiche à régulariser',
                'slug' => 'fiche-a-regulariser',
                'class_name' => 'App\\Models\\RegularizationSheet',
                'creation_handler_class' => 'App\\Services\\Regularization\\RegularizationDocumentHandler',
                'enrichment_handler_class' => 'App\\Services\\Regularization\\RegularizationDocumentEnrichmentHandler',
                'relation_name' => 'regularization_sheet',
                'icon' => '🧾',
                'color' => 'orange',
                'dashboard_title' => 'Fiches à régulariser',
                'dashboard_subtitle' => 'À traiter',
                'view_route' => '/fiches-a-regulariser-a-valider',
                'view_own_route' => '/mes-fiches-a-regulariser',
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],

            /*
            |--------------------------------------------------------------------------
            | FACTURE FOURNISSEUR
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'INVOICE_PROVIDER',
                'name' => 'Facture fournisseur',
                'slug' => 'facture-fournisseur',
                'class_name' => 'App\\Models\\Finance\\InvoiceProvider',
                'creation_handler_class' => 'App\\Services\\InvoiceProvider\\InvoiceProviderDocumentHandler',
                'enrichment_handler_class' => 'App\\Services\\InvoiceProvider\\InvoiceProviderDocumentEnrichmentHandler',
                'relation_name' => 'invoice_provider',
                'icon' => '🧾',
                'color' => 'indigo',
                'dashboard_title' => 'Factures fournisseurs',
                'dashboard_subtitle' => 'À traiter',
                'view_route' => '/factures-fournisseurs',
                'view_own_route' => null,
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
            ],
        ];

        foreach ($documentTypes as $documentType) {

            DocumentType::updateOrCreate(
                [
                    'code' => $documentType['code'],
                ],
                $documentType
            );
        }
    }
}