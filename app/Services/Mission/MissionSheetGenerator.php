<?php

namespace App\Services\Mission;

use PhpOffice\PhpSpreadsheet\IOFactory;

class MissionSheetGenerator
{
    public function generate($mission)
    {
        $templatePath = storage_path(
            'app/templates/mission_template.xlsx'
        );

        $spreadsheet = IOFactory::load($templatePath);

        $sheet = $spreadsheet->getActiveSheet();

        // $sheet->setCellValue('B3', $mission->reference);
        $sheet->setCellValue('A6', "Marcel GABIN");
        // $sheet->setCellValue('B5', $mission->department);
        // $sheet->setCellValue('G4', $mission->departure_date);
        // $sheet->setCellValue('G5', $mission->return_date);

        $directory = storage_path('app/generated');

        if (!file_exists($directory)) {
    mkdir($directory, 0777, true);
}

        $outputPath = storage_path(
            'app/generated/mission_' .
            $mission->id .
            '.xlsx'
        );

        $writer = IOFactory::createWriter(
            $spreadsheet,
            'Xlsx'
        );

        $writer->save($outputPath);

        return $outputPath;
    }
}