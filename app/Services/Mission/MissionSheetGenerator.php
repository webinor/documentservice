<?php

namespace App\Services\Mission;

use App\Models\Misc\Document;
use App\Services\Mission\Generators\MissionSheetExcelGenerator;


class MissionSheetGenerator
{
   public function generate(Document $document)
{
    $outputPath = app(MissionSheetExcelGenerator::class)->generate($document);

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


}