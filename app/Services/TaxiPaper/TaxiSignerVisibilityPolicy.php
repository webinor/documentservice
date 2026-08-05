<?php

namespace App\Services\TaxiPaper;

use App\Contracts\SignerVisibilityPolicy;

use function PHPUnit\Framework\isEmpty;

class TaxiSignerVisibilityPolicy implements SignerVisibilityPolicy
{
    public function isVisible(array $participant): bool
    {
        // return 

        if ($participant['status'] != "APPROVED") {

            
        return false;
        

        }



        // if (isEmpty($participant['user'])) {
            

        // throw new \Exception(json_encode($participant), 1);

        
        // }
        $responsibilities = $participant['user']['responsibilities'];
        

        

        if ($participant['source_type'] == "OWNER") {

            
        // return true;
        

        }

        if (
    $participant['source_type'] == "OWNER" &&
    !empty(array_intersect(
        $responsibilities,
        ["SIGNATORY", "HEAD_OF_DEPARTMENT"]
    ))
) {
   


            
        throw new \Exception(json_encode($participant['user']['responsibilities']), 1);

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