<?php

namespace App\Services\Absence;


use App\Contracts\SignerVisibilityPolicy;

class AbsenceSignerVisibilityPolicy implements SignerVisibilityPolicy
{
    public function isVisible(array $participant, array $documentData = []): bool
    {
        // return 

        if ($participant['status'] != "APPROVED") {

            
        return false;
        

        }

        if (isset($documentData['context']) && $documentData['context'] == "leave_order" && in_array($participant['source_value'], ["OWNER"]) ) {
            
        //  throw new \InvalidArgumentException(
        //         $documentData['context']
        //     );

        return false;
        
        }

     

        if ($participant['signature_visibility'] == "IF_APPROVED" && $participant['status'] == "APPROVED") {

            
        return true;
        

        }

           

       

        if (isset($documentData['context']) && $documentData['context'] == "leave_order" ) {
            
        $source_values = [
            'DIRECT_MANAGER',
            'HEAD_OF_DEPARTMENT',
            'SIGNATORY',
        ];
        
        }
        else{

        $source_values = [
            'OWNER',
            'DIRECT_MANAGER',
            'HEAD_OF_DEPARTMENT',
            'SIGNATORY',
        ];

        }

        if (in_array($participant['source_value'], $source_values)) {

        return true;
           
        }

        return false;
    }
}