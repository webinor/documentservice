<?php

namespace App\Services\Mission;

use App\Contracts\SignerVisibilityPolicy;
use Illuminate\Support\Str;

class MissionSignerVisibilityPolicy implements SignerVisibilityPolicy
{
    public function isVisible(array $participant): bool
    {

     if ($participant['status'] != "APPROVED") {

            
        return false;
        

        }

          if ($participant['source_type'] == "OWNER") {

            
        return true;
        

        }

        if (in_array($participant['source_value'], [
            // 'DIRECT_MANAGER',
            'HEAD_OF_DEPARTMENT',
            'SIGNATORY',
        ])) {

        return true;
           
        }



         if (in_array(Str::lower($participant['user']['role']), [

            'responsable logistique',
            'directeur operations',
            'directeur general',
            'tresorier'
        ])) {

        //   throw new \Exception("ouiiiiiiiiiiiiiiiiiiii", 1);

        return true;
           
        }

        

        return false;
    }
}