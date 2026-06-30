<?php

namespace App\Services\Mission\Generators;

use App\Managers\DocumentEnrichmentManager;
use App\Models\Misc\Document;
use App\Models\Mission;
use App\Services\Templates\MissionTemplateDataBuilder;
use App\Services\Document\DocumentEnricher;
use App\Services\SignerVisibilityPolicyFactory;
use App\Services\Workflow\WorkflowParticipantService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MissionSheetExcelGenerator
{
    public function generate(Document $document): string
    {

    $templatePath = storage_path(
        'app/templates/mission_template.xlsx'
    );


        $document->load("document_type");


        // $document = app(DocumentEnricher::class)->enrichDocument($document, request()->bearerToken()) ;

        $document = app(DocumentEnrichmentManager::class)->enrich($document);


        throw new \Exception(json_encode($document));


        $dataParticipants = app(WorkflowParticipantService::class)->getParticipants(
    $document,
    request()->bearerToken()
);

$participants = $dataParticipants['participants'];
$business_signatures = $dataParticipants['business_signatures'];

    $policy = SignerVisibilityPolicyFactory::make(
    $document->document_type->slug
);

        // throw new Exception(json_encode($document['mission']['actor_details']), 1);
        throw new Exception(json_encode($document['actor_details']), 1);


  $visibleParticipants = collect($participants)
    ->filter(fn ($p) => $policy->isVisible($p))
    ->values()
    ->toArray();


    $headOfDepartmentUserId = $document['mission']['actor_details']['department_data']['head_of_department']['user_id'] ?? null;

    $head_of_department_data = $this->getUser($headOfDepartmentUserId);

    // throw new Exception(json_encode($head_of_department_data), 1);
        

    $spreadsheet = IOFactory::load($templatePath);

    $sheet = $spreadsheet->getActiveSheet();

    $data = app(MissionTemplateDataBuilder::class)->build($document, $head_of_department_data);

    $mapping = config('templates.mission.cells');

    $signatureMapping = config(
    'templates.mission.signatures'
);

    foreach ($mapping as $cell => $key) {

    // if (!isset($data[$key])) {
    //     throw new \Exception(json_encode($data), 1);
        
    // }
        $sheet->setCellValue(
            $cell,
            $data[$key] ?? ''
        );
    }



    foreach ($visibleParticipants as $participant) {




    $key = $this->getParticipantKey(
        $participant
    );

    if (
        !$key ||
        !isset($signatureMapping['top'][$key])
    ) {

        //   throw new \Exception(json_encode($participant['user']['name']), 1);

        continue;
    }

    $config = $signatureMapping['top'][$key];

    $user = $participant['user'];

    

foreach ($config['placements'] as $placement) {

    $this->insertSignature(
        $sheet,
        $user['signatureUrl'],
        $placement['signature']
    );

    $sheet->setCellValue(
        $placement['name'],
        $user['name'] ?? '--'
    );

    $sheet->setCellValue(
        $placement['date'],
        $participant['validated_at']
            ? date(
                'd/m/Y H:i',
                strtotime($participant['validated_at'])
            )
            : '--'
    );
}
    }



  $lastTableRow  =  $this->renderTables(
    $sheet,
    config('templates.mission.tables'),
    $data
);


$startSignatureRow = $lastTableRow + 4;



###################################
$this->renderBottomSignatures(
    $sheet,
    $signatureMapping['bottom'],
    $visibleParticipants,
    $startSignatureRow
);

#####################################
    $directory = storage_path('app/generated');

    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    $outputPath = storage_path(
        'app/generated/mission_' .
        $document->mission->code .
        '.xlsx'
    );

    $writer = IOFactory::createWriter(
        $spreadsheet,
        'Xlsx'
    );





    #############################

// $sheet->getPageSetup()
//     ->setOrientation(
//         PageSetup::ORIENTATION_LANDSCAPE
//     );



    $sheet->getPageSetup()
    ->setPaperSize(
        PageSetup::PAPERSIZE_A4
    );

    $sheet->getPageSetup()
    ->setHorizontalCentered(true);

    $sheet->getPageSetup()
    ->setFitToWidth(1);



    ############################





    $writer->save($outputPath);

//        exec(sprintf(
//     'libreoffice2 --headless --convert-to pdf --outdir %s %s',
//     escapeshellarg(dirname($outputPath)),
//     escapeshellarg($outputPath)
// ));

 $profile = storage_path('app/libreoffice-profile');

if (!is_dir($profile)) {
    mkdir($profile, 0777, true);
}

$command = sprintf(
    'libreoffice --headless -env:UserInstallation=file://%s --convert-to pdf --outdir %s %s 2>&1',
    str_replace('\\', '/', $profile),
    escapeshellarg(dirname($outputPath)),
    escapeshellarg($outputPath)
);

exec($command, $output, $returnCode);


return str_replace('.xlsx', '.pdf', $outputPath);
        
    
    }



private function getUser($userId){


        $baseUrl = config("services.user_service.base_url");
       
                            $response = Http::acceptJson()->get(
                                $baseUrl . "/{$userId}"
                            );

                            if ($response->successful()) {
                             return   $value =
                                    $response->json()["user"] ??
                                    $response->json();
                            }


}

private function renderBottomSignatures(
    $sheet,
    array $config,
    array $participants,
    int $lastTableRow
) {
    $baseRow = $lastTableRow + $config['start_after_table_offset'];

    foreach ($participants as $participant) {

            // throw new Exception(json_encode($participants), 1);


        $ki = $this->getParticipantKey($participant , 'bottom.positions');

        if ($participant['user']['role'] == "Tresorier") {
            
            // throw new Exception(json_encode($config['positions']), 1);
            // throw new Exception(json_encode($config['positions'][$ki]), 1);

        }

        if (!$ki || !isset($config['positions'][$ki])) {

          if ($participant['user']['role'] == "Tresorier") {
            
            // throw new Exception(json_encode($ki), 1);

        }

            continue;
        }

        // $source = $participant['source_type'] ? ''

        $col = $config['positions'][$ki];

        $user = $participant['user'];

        // if ($participant['source_type'] == "OWNER") {
        if ($ki == "CC") {

            // throw new Exception(json_encode($user), 1);


            
        }

        // signature
        $this->insertSignature(
            $sheet,
            $user['signatureUrl'],
            $col . ($baseRow + $config['rows']['signature'])
        );

        // name
        $sheet->setCellValue(
            $col . ($baseRow + $config['rows']['name']),
            $user['name'] ?? '--'
        );

        // date
        $sheet->setCellValue(
            $col . ($baseRow + $config['rows']['date']),
            $participant['validated_at']
                ? date('d/m/Y H:i', strtotime($participant['validated_at']))
                : '--'
        );
    }
}

private function renderTables(
    $sheet,
    array $tables,
    array $data
) {

$lastRow = null;

    foreach ($tables as $tableConfig) {

     $lastRow =   $this->renderTable(
            $sheet,
            $tableConfig,
            $data
        );

    }

    return $lastRow;

}


private function renderTable(
    $sheet,
    array $config,
    array $data
) {

    $rows = $data[
        $config['source']
    ] ?? [];

    // throw new Exception(json_encode($data), 1);
    

    if (empty($rows)) {
        return;
    }

    $startRow = $config['start_row'];

    $templateRow = 33;

$mergedRanges = $sheet->getMergeCells();

$filtered = [];

foreach ($mergedRanges as $range) {

    if (str_contains($range, (string)$templateRow)) {
        $filtered[] = $range;
    }
}




    foreach ($rows as $index => $rowData) {

        $currentRow = $startRow + $index;

        $shouldInsert = sizeof($rows) > $index ;

        if ($shouldInsert) {

        if ($index > 0) {


            $sheet->insertNewRowBefore(
                $currentRow,
                1
            );

        }

            foreach ($filtered as $range) {

        // ex: A33:C33
        [$start, $end] = explode(':', $range);

        $newStart = preg_replace(
            '/\d+/',
            $currentRow,
            $start
        );

        $newEnd = preg_replace(
            '/\d+/',
            $currentRow,
            $end
        );

        $sheet->mergeCells(
            $newStart . ':' . $newEnd
        );
    }

        foreach (
            $config['columns']
            as $column => $field
        ) {

            $sheet->setCellValue(
                $column . $currentRow,
                $rowData[$field] ?? '--'
            );

        }

    }

    }

    $lastDataRow = $currentRow;
    $totalRow = $lastDataRow + 1;
    $total = collect($rows)->sum('total');

//     $sheet->setCellValue(
//     "C{$totalRow}",
//     "TOTAL"
// );

// $sheet->setCellValue(
//     "H{$totalRow}",
//     $total
// );

if (!empty($config['footer']['enabled'])) {

    $totalRow = $lastDataRow + 1;

    $total = collect($rows)->sum('total');
    // $advance = $sheet->getCell('E28')->getValue();
    $advance = $data['advance.amount'];

    // throw new Exception($total, 1);
    

    // $sheet->setCellValue(
    //     $config['footer']['label_cell'] . $totalRow,
    //     $config['footer']['label']
    // );

    $sheet->setCellValue(
        $config['footer']['value_cell'] . $totalRow,
        $total
    );

    $sheet->setCellValue(
    $config['footer']['value_cell'] . ($totalRow + 1),
    $data['advance.amount']);

    $sheet->setCellValue(
    $config['footer']['value_cell'] . ($totalRow + 2),
    $advance - $total);
}
     return $currentRow; // 👈 IMPORTANT

        // return $totalRow + 2;

}

private function insertSignature(
    $sheet,
    $signatureUrl,
    $cell
) {
    if (empty($signatureUrl)) {
        return;
    }

    try {

        $response = Http::withToken(
    request()->bearerToken()
)->get($signatureUrl);

        if (!$response->ok()) {
            return;
        }

        $tmpDir = storage_path('app/temp-signatures');

        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $tmpFile = $tmpDir . '/' . md5($signatureUrl) . '.jpg';

        file_put_contents(
            $tmpFile,
            $response->body()
        );

        $drawing = new Drawing();

        $drawing->setPath($tmpFile);

        $drawing->setCoordinates($cell);

        // Décalage horizontal
$drawing->setOffsetX(20);

// Décalage vertical
$drawing->setOffsetY(5);

        $drawing->setHeight(40);

        $drawing->setWorksheet($sheet);

    } catch (\Exception $e) {

        Log::error(
            'Unable to load signature',
            [
                'url' => $signatureUrl,
                'message' => $e->getMessage()
            ]
        );
    }
}


private function getParticipantKey(array $participant , string $context = 'top')
{
    if (
        !empty($participant['source_type']) 
        && in_array($participant['source_type'], ["OWNER"])
    ) {
        // throw new Exception(json_encode($participant['source_type']), 1);

        return $participant['source_type'];
    }

    if (
        !empty($participant['source_value'])
        && isset(
            config('templates.mission.signatures.'.$context)
            [$participant['source_value']]
        )
    ) {
        // throw new Exception(json_encode($participant), 1);
        
        return //"HEAD_OF_DEPARTMENT";//
         $participant['source_value'];
    }

    $role = strtolower(
        $participant['user']['role'] ?? ''
    );

    if (strpos($role, 'operation') !== false) {
        return 'DO';
    }

    if (strpos($role, 'general') !== false) {
        return 'DG';
    }



    if (in_array($role , ['responsable logistique']) ) {
        // throw new Exception("Error Processing Request", 1);
        
        return 'CL';
    }

     if (in_array($role , ['tresorier']) ) {
        // throw new Exception("Error Processing Request", 1);
        
        return 'CC';
    }

    

    

        // throw new Exception($role, 1);


    return null;
    // throw new \Exception(json_encode($participant), 1);
}
}