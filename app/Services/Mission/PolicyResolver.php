<?php

namespace App\Services\Mission;


class PolicyResolver
{
    public function resolve($policies)
    {
        $scorer = new PolicyScorerService();

        return $policies->sortByDesc(function ($policy) use ($scorer) {
            return $scorer->score($policy);
        })->first();
    }
}