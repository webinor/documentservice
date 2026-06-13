<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Misc\DocumentType;

class SeedDocumentTypeDashboardMetadata extends Command
{
    protected $signature = 'document-types:dashboard';

    protected $description =
        'Initialise les métadonnées dashboard des types documentaires';

    public function handle()
    {
        $configs = [

    'papier-taxi' => [
        'icon' => '🚕',
        'color' => 'orange',
        'dashboard_order' => 1,
        'dashboard_title' => 'Papiers Taxi',
        'dashboard_subtitle' => 'À valider',
        'view_route' => '/papiers-taxi-a-suivre',
        'create_route' => '/nouveau-document?type=papier-taxi',
    ],

    'mission' => [
        'icon' => '✈️',
        'color' => 'blue',
        'dashboard_order' => 2,
        'dashboard_title' => 'Missions',
        'dashboard_subtitle' => 'En attente',
        'view_route' => '/missions-a-suivre',
        'create_route' => '/nouveau-document?type=mission',
    ],

    'demande-achat' => [
        'icon' => '🛒',
        'color' => 'green',
        'dashboard_order' => 3,
        'dashboard_title' => 'Demandes d\'achat',
        'dashboard_subtitle' => 'À traiter',
        'view_route' => '/achats-a-suivre',
        'create_route' => '/nouveau-document?type=demande-achat',
    ],

    'facture-fournisseur-informatique' => [
        'icon' => '🧾',
        'color' => 'purple',
        'dashboard_order' => 4,
        'dashboard_title' => 'Factures',
        'dashboard_subtitle' => 'À contrôler',
        'view_route' => '/workflow/documents/facture-fournisseur-informatique',
        'create_route' => '/nouveau-document/facture-fournisseur-informatique',
    ],

];

        foreach ($configs as $slug => $config) {

            $type = DocumentType::where('slug', $slug)->first();

            if (!$type) {
                $this->warn("Type introuvable : {$slug}");
                continue;
            }

            $type->update($config);

            $this->info("Mis à jour : {$slug}");
        }

        $this->info('Dashboard configuré avec succès.');

        return Command::SUCCESS;
    }
}