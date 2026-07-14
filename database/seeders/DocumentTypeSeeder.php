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

            use App\Models\RegularizationSheet;
use App\Services\Regularization\RegularizationDocumentEnrichmentHandler;
use App\Services\Regularization\RegularizationDocumentHandler;

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




[
    'code' => 'REGULARIZATION_SHEET',
    'name' => 'Fiche à régulariser',
    'slug' => 'fiche-a-regulariser',

    'class_name' => RegularizationSheet::class,

    'creation_handler_class' =>
        RegularizationDocumentHandler::class,

    'enrichment_handler_class' =>
        RegularizationDocumentEnrichmentHandler::class,

    'relation_name' => 'regularization_sheet',

    'icon' => '🧾',

    'color' => 'orange',

    'dashboard_order' => 10,

    'show_in_dashboard' => true,

    'dashboard_title' => 'Fiches à régulariser',

    'dashboard_subtitle' => 'À traiter',

    'view_route' => '/fiches-a-regulariser',

    'create_route' => '/nouveau-document?type=fiche-a-regulariser',

    'return_policy' => 'ROLE',

    'reception_mode' => 'WORKFLOW_DRIVEN',
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

   DocumentType::updateOrCreate(
    ['code' => $config['code']],
    [
        'name'                       => $config['name'],
        'slug'                       => $config['slug'] ?? Str::slug($config['name']),
        'class_name'                 => $config['class_name'] ?? null,
        'creation_handler_class'     => $config['creation_handler_class'] ?? null,
        'enrichment_handler_class'   => $config['enrichment_handler_class'] ?? null,
        'relation_name'              => $config['relation_name'] ?? null,
        'icon'                       => $config['icon'] ?? '📄',
        'color'                      => $config['color'] ?? 'blue',
        'dashboard_order'            => $config['dashboard_order'] ?? 0,
        'show_in_dashboard'          => $config['show_in_dashboard'] ?? true,
        'dashboard_title'            => $config['dashboard_title'] ?? $config['name'],
        'dashboard_subtitle'         => $config['dashboard_subtitle'] ?? '',
        'view_route'                 => $config['view_route'] ?? null,
        'create_route'               => $config['create_route'] ?? null,
        'return_policy'              => $config['return_policy'] ?? 'ROLE',
        'reception_mode'             => $config['reception_mode'] ?? 'WORKFLOW_DRIVEN',
    ]
);
}

        

           DB::commit();
    
            
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    
    }
}
