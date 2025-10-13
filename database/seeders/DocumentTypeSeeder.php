<?php

namespace Database\Seeders;

use App\Models\Art;
use App\Models\Tax;
use Illuminate\Support\Str;
use App\Models\FormalNotice;
use Illuminate\Database\Seeder;
use App\Models\Misc\DocumentType;
use Illuminate\Support\Facades\DB;
use App\Models\Misc\AttachmentType;
use App\Models\DepartmentDocumentType;
use Database\Seeders\AttachmentTypeSeeder;
use Database\Seeders\LedgerCodeTypeSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        try {

            DB::beginTransaction();


// Configuration centralisée
$documentTypesConfig = [
    

    [
        'name'          => 'Courrier Impots',
        'class_name'    => Tax::class,
        'relation_name' => 'tax',
        'reception_mode'=> 'AUTO_BY_ROLE',
    ],

        [
        'name'          => 'Courrier Mises en demeure',
        'class_name'    => FormalNotice::class,
        'relation_name' => 'formal_notice',
        'reception_mode'=> 'AUTO_BY_ROLE',
    ],

         [
        'name'          => 'Courrier Art',
        'class_name'    => Art::class,
        'relation_name' => 'art',
        'reception_mode'=> 'AUTO_BY_ROLE',
    ],
    
];

// Génération de la sequence dynamiquement
$sequence = array_map(fn ($config) => fn () => [
    'code'           => Str::random(10),
    'name'           => $config['name'],
    'class_name'     => $config['class_name']     ?? null,
    'relation_name'  => $config['relation_name']  ?? null,
    'reception_mode' => $config['reception_mode'],
    'slug'           => Str::slug($config['name']),
], $documentTypesConfig);
          
            
          DocumentType::factory()
    ->count(count($documentTypesConfig))
    ->state(new Sequence(...$sequence))
    ->create();

            /*DepartmentDocumentType::factory()
            ->count(1)
            ->state(new Sequence(
                [ 'department_id'=>67,'document_type_id'=>1],
            ))
            ->create();*/


           /* AttachmentType::factory()
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
            ->create();*/


            $this->call([
        AttachmentTypeSeeder::class,
       // LedgerCodeTypeSeeder::class,
    ]);

            DB::commit();
    
            
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    
    }
}
