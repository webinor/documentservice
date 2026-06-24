<?php

namespace App\Services\Mission;

use App\Contracts\SignerVisibilityPolicy;

class MissionSignerVisibilityPolicy implements SignerVisibilityPolicy
{
    public function isVisible(array $participant): bool
    {
          if ($participant['source_type'] == "OWNER") {

            
        return true;
        

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