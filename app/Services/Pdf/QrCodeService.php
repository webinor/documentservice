<?php

namespace App\Services\Pdf;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Génère un QR Code en base64
     */
    public function generate($content)
    {
        return base64_encode(
            QrCode::format('png')
                ->size(150)
                ->margin(1)
                ->generate($content)
        );
    }
}