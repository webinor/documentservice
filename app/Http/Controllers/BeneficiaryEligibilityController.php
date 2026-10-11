<?php

namespace App\Http\Controllers;

use App\Services\BeneficiaryEligibilityService;
use Illuminate\Http\JsonResponse;

class BeneficiaryEligibilityController extends Controller
{
    protected BeneficiaryEligibilityService $eligibilityService;

    public function __construct(
        BeneficiaryEligibilityService $eligibilityService
    ) {
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Vérifie si un bénéficiaire peut recevoir un nouveau document.
     *
     * GET /api/beneficiaries/{beneficiaryId}/eligibility
     */
    public function show(int $beneficiaryId): JsonResponse
    {
        $result = $this->eligibilityService->evaluate(
            $beneficiaryId
        );

        return response()->json(
            $result,
            !empty($result['success']) ? 200 : 422
        );
    }
}