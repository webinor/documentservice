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

        if ($participant['source_type'] == "OWNER") {

            
        // return true;
        

        }

        if (in_array($participant['source_value'], [
            'DIRECT_MANAGER',
            'SIGNATORY',
        ])) {

        return true;
           
        }

        return false;
    }
}