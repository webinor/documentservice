<?php

namespace App\Services\TaxiPaper;

use App\Contracts\SignerVisibilityPolicy;

class TaxiSignerVisibilityPolicy implements SignerVisibilityPolicy
{
    public function isVisible(array $participant): bool
    {
        // return 

        if ($participant['status'] != "APPROVED") {

            
        return false;
        

        }

        if ($participant['source_type'] == "OWNER") {

            
        // return true;
        

        }

        if (in_array($participant['source_value'], [
            'DIRECT_MANAGER',
            'HEAD_OF_DEPARTMENT',
            'SIGNATORY',
        ])) {

        return true;
           
        }

        return false;
    }
}