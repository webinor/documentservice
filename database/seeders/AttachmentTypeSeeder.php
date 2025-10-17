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
            'Facture originale' => false,
            'Contrat de prestation' => false,
            'Contrat' => false,
            'Bon de regularisation' => false,
            'Bon de commande' => false,
            'Bon de livraison' => false,
            'Devis' => false,
            'Attestation' => false,
            'Preuve de paiement' => false,
            'Note avoir' => false,
            'Autre' => false,
        ];

        foreach ($engagementTypes as $type => $isRequired) {
            AttachmentType::create([
                'attachment_type_category_id' => $engagementCat->id,
                'name' => $type,
                'slug' => Str::slug($type),
                'attachment_number_required'=>$isRequired
            ]);
        }

        // Types pour la catégorie Payment
        $paymentTypes = [
           // 'Ordre de virement',
            'Preuve de paiement'=>true,
            'Attestation de reglement'=>false,
            'Numero de piece Facture'=>true,
        ];

        foreach ($paymentTypes as $type  => $isRequired ) {
            AttachmentType::create([
                'attachment_type_category_id' => $paymentCat->id,
                'name' => $type,
                'slug' => Str::slug($type),
                'attachment_number_required'=>$isRequired
            ]);
        }
    }
}
