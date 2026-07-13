<?php

namespace App\Services\Absence;


use App\Contracts\SignerVisibilityPolicy;

class AbsenceSignerVisibilityPolicy implements SignerVisibilityPolicy
{
    public function isVisible(array $participant): bool
    {
        // return 

        if ($participant['status'] != "APPROVED") {

            
        return false;
        

        }

        if ($participant['signature_visibility'] == "IF_APPROVED" && $participant['status'] == "APPROVED") {

            
        return true;
        

        }

        if (in_array($participant['source_value'], [
            'OWNER',
            'DIRECT_MANAGER',
            'HEAD_OF_DEPARTMENT',
            'SIGNATORY',
        ])) {

        return true;
           
        }

        return false;
    }
}