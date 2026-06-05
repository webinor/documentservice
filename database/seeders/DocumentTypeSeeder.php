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
use App\Models\Mission;
use App\Models\Purchase;
use App\Models\PurchaseRequest;
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
    

    // [
    //     'name'          => 'Courrier Impots',
    //     'class_name'    => Tax::class,
    //     'relation_name' => 'tax',
    //     'reception_mode'=> 'AUTO_BY_ROLE',
    // ],

    //     [
    //     'name'          => 'Courrier Mises en demeure',
    //     'class_name'    => FormalNotice::class,
    //     'relation_name' => 'formal_notice',
    //     'reception_mode'=> 'AUTO_BY_ROLE',
    // ],

    //      [
    //     'name'          => 'Courrier Art',
    //     'class_name'    => Art::class,
    //     'relation_name' => 'art',
    //     'reception_mode'=> 'AUTO_BY_ROLE',
    // ],

    [
                'code' => 'MISSION',
                'name' => 'Mission',
                'class_name'    => Mission::class,
                'relation_name' => 'mission',
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],


              [
                'code' => 'PURCHASE_REQUEST',
                'name' => 'Achat',
                'class_name'    => PurchaseRequest::class,
                'relation_name' => 'purchase_request',
                'return_policy' => 'ROLE',
                'reception_mode' => 'WORKFLOW_DRIVEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
    
];

// Génération de la sequence dynamiquement
// $sequence = array_map(fn ($config) => fn () => [
//     'code'           => $config['code'] ?? Str::random(10),
//     'name'           => $config['name'],
//     'class_name'     => $config['class_name']     ?? null,
//     'relation_name'  => $config['relation_name']  ?? null,
//     'reception_mode' => $config['reception_mode'],
//     'slug'           => Str::slug($config['name']),
// ], $documentTypesConfig);
          
            
//           DocumentType::factory()
//     ->count(count($documentTypesConfig))
//     ->state(new Sequence(...$sequence))
//     ->create();

foreach ($documentTypesConfig as $config) {

    $exists = DocumentType::where('code', $config['code'] ?? Str::slug($config['name']))->exists();

    if ($exists) {
        continue;
    }

    DocumentType::create([
        'code'           => $config['code'] ?? Str::slug($config['name']),
        'name'           => $config['name'],
        'class_name'     => $config['class_name'] ?? null,
        'relation_name'  => $config['relation_name'] ?? null,
        'reception_mode' => $config['reception_mode'],
        'slug'           => Str::slug($config['name']),
    ]);
}

        

           DB::commit();
    
            
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    
    }
}
