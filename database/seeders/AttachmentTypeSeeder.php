<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\Misc\AttachmentType;
use App\Models\AttachmentTypeCategory;

class AttachmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Création des catégories
        $engagementCat = AttachmentTypeCategory::create([
            'name' => 'Engagement',
            'slug' => Str::slug('Engagement'),
        ]);

        $paymentCat = AttachmentTypeCategory::create([
            'name' => 'Paiement',
            'slug' => Str::slug('Paiement'),
        ]);

        // Types pour la catégorie Engagement
        $engagementTypes = [
            'Facture originale',
            'Contrat',
            'Bon de regularisation',
            'Bon de commande',
            'Bon de livraison',
            'Devis',
            'Attestation',
            'Preuve de paiement',
            'Note avoir',
            'Autre',
        ];

        foreach ($engagementTypes as $type) {
            AttachmentType::create([
                'attachment_type_category_id' => $engagementCat->id,
                'name' => $type,
                'slug' => Str::slug($type),
            ]);
        }

        // Types pour la catégorie Payment
        $paymentTypes = [
           // 'Ordre de virement',
            'Preuve de paiement',
            'Attestation de reglement',
            'Numero de piece Facture'
        ];

        foreach ($paymentTypes as $type) {
            AttachmentType::create([
                'attachment_type_category_id' => $paymentCat->id,
                'name' => $type,
                'slug' => Str::slug($type),
            ]);
        }
    }
}
