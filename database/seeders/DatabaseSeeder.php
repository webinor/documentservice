<?php

namespace Database\Seeders;

use App\Models\DepartmentDocumentType;
use App\Models\Misc\AttachmentType;
use App\Models\Misc\DocumentType;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Str;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

       

        try {

            DB::beginTransaction();

          
            
            DocumentType::factory()
            ->count(3)
            ->state(new Sequence(
                ['id'=>1 , 'name'=>'Facture Fournisseur','slug'=>Str::slug("Facture Fournisseur")],
                ['id'=>2 , 'name'=>'Courrier entrant','slug'=>Str::slug("Courrier entrant")],
                ['id'=>3 , 'name'=>'Demande de congé','slug'=>Str::slug("Demande de congé")],
            ))
            ->create();

            DepartmentDocumentType::factory()
            ->count(1)
            ->state(new Sequence(
                [ 'department_id'=>67,'document_type_id'=>1],
            ))
            ->create();


            AttachmentType::factory()
            ->count(10)
            ->state(new Sequence(
                ['name'=>'Facture originale','slug'=>Str::slug("Facture originale")],
                ['name'=>'Contrat','slug'=>Str::slug("Contrat")],
                ['name'=>'Bon de regularisation','slug'=>Str::slug("Bon de regularisation")],
                ['name'=>'Bon de commande','slug'=>Str::slug("Bon de commande")],
                ['name'=>'Bon de livraison','slug'=>Str::slug("Bon de livraison")],
                ['name'=>'Devis','slug'=>Str::slug("Devis")],
                ['name'=>'Attestation','slug'=>Str::slug("Attestation")],
                ['name'=>'Preuve de paiement','slug'=>Str::slug("Preuve de paiement")],
                ['name'=>'Note avoir','slug'=>Str::slug("Note avoir")],
                ['name'=>'Autre','slug'=>Str::slug("Autre")]
            ))
            ->create();

            DB::commit();
    
            
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }
}
