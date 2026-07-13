<?php

namespace App\Services;

use App\Contracts\SignerVisibilityPolicy;
use App\Services\Absence\AbsenceSignerVisibilityPolicy;
use App\Services\Mission\MissionSignerVisibilityPolicy;
use App\Services\TaxiPaper\TaxiSignerVisibilityPolicy;

class SignerVisibilityPolicyFactory
{
    protected static $policies = [
        'papier-taxi' => TaxiSignerVisibilityPolicy::class,
        'mission'     => MissionSignerVisibilityPolicy::class,
        'demande-d-absence' => AbsenceSignerVisibilityPolicy::class
    ];

    public static function make(string $documentType): SignerVisibilityPolicy
    {
        $policyClass = self::$policies[$documentType]?? null;//            ?? DefaultSignerVisibilityPolicy::class;

        return new $policyClass();
    }
}