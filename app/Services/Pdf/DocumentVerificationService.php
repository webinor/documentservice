<?php

namespace App\Services\Pdf;

use App\Models\DocumentVerification;
use Illuminate\Support\Str;

class DocumentVerificationService
{



function generateVerificationCode($length = 8)
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ234679';

    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $code;
}
    public function create($document): array
    {

        $verificationCode = $this->generateVerificationCode();


        $verification = DocumentVerification::create([
            'document_id' => $document['id'],
            'verification_code' => $verificationCode,
            'version' => '1.0',
            'snapshot' => [
        'reference' => $document['reference'],
        'type' => $document['document_type']['name'],
        'generated_at' => now(),
    ],
            'is_valid' => true,
        ]);


        return [
            'code' => $verificationCode,

            'url' => config('app.url')
                .'/verify/'.$verificationCode,
        ];
    }
}