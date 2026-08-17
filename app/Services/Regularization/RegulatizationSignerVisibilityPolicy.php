<?php

namespace App\Services\Regularization;



use App\Contracts\SignerVisibilityPolicy;
use App\Services\FinancialDocumentSignerVisibilityPolicy;

class RegulatizationSignerVisibilityPolicy extends FinancialDocumentSignerVisibilityPolicy
{
    // public function isVisible(array $participant, array $documentData = []): bool
    // {
    //     // return 

    //     if ($participant['status'] != "APPROVED") {

            
    //     return false;
        

    //     }

    //         if ($participant['signature_visibility'] == "IF_APPROVED" && $participant['status'] == "APPROVED") {

            
    //     // return true;
        

    //     }

    //     if (in_array($participant['source_value'], [
    //         'DIRECT_MANAGER',
    //         'HEAD_OF_DEPARTMENT',
    //         'SIGNATORY',
    //     ])) {

    //     return true;
           
    //     }

    //     return false;
    // }
}