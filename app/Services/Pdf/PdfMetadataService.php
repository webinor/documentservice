<?php

namespace App\Services\Pdf;

use App\Services\Pdf\DocumentVerificationService;
use App\Services\Pdf\QrCodeService;
use Illuminate\Support\Str;

class PdfMetadataService
{
  protected QrCodeService $qrCodeService;
  protected DocumentVerificationService $verificationService;

       public function __construct(
        DocumentVerificationService $verificationService,
        QrCodeService $qrCodeService
    )
    {
        $this->verificationService = $verificationService;
        $this->qrCodeService = $qrCodeService;
    }

    public function build(array $document): array
    {

        $verification =
            $this->verificationService->create($document);

        // $verificationReference = strtoupper(Str::random(4))
        //     . '-'
        //     . strtoupper(Str::random(4))
        //     . '-'
        //     . strtoupper(Str::random(4));

        //  $verificationUrl =
        //     config('app.url')
        //     . '/document-verification/'
        //     . $verificationReference;


        return [

            'document_type' => $document['document_type']['name'] ?? null,

            'document_number' => $document['reference'] ?? null,

            'version' => '1.0',

            'generated_at' => now(),

            'generated_by' => 'CAS CONNECT',

             'verification_reference' =>
                $verification['code'],


            'qr_code' =>
                $this->qrCodeService->generate(
                    $verification['url']
                ),


        ];
    }
}