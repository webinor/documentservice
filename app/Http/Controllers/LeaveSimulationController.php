<?php

namespace App\Http\Controllers;

use App\DTO\LeaveCalculationRequest;
use App\Services\Absence\LeaveCalculatorService;
use Illuminate\Http\Request;

class LeaveSimulationController extends Controller
{
    public function simulate(
        Request $request,
        LeaveCalculatorService $calculator
    ) {
        $data = new LeaveCalculationRequest(
            $request->all()
        );

        $result = $calculator->calculateWithBalance(
            $data,
            $request->bearerToken()
        );

        if ($result === null) {
            return response('null', 200)
                ->header('Content-Type', 'application/json');
        }

        return response()->json($result);
    }
}