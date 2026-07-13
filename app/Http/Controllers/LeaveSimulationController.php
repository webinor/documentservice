<?php

namespace App\Http\Controllers;

use App\DTO\LeaveCalculationRequest;
use App\Services\Absence\LeaveCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LeaveSimulationController extends Controller
{
    public function simulate(
        Request $request,
        LeaveCalculatorService $calculator
    ) {

        $data = new LeaveCalculationRequest(
            $request->all()
        );

        // Calcul des jours
        $result = $calculator->calculate($data);

        // Récupération du solde dans le Employee Service
        $balanceResponse = Http::withToken($request->bearerToken())
            ->acceptJson()
            ->get(
                config('services.user_service.base_url')
                . '/leave-balances/' . $data->employeeId,
                [
                    'year' => now()->year,
                ]
            );

        if (!$balanceResponse->successful()) {
            return response()->json([
                'message' => 'Impossible de récupérer le solde de congés.',
                'body' => $balanceResponse->body()
            ], 500);
        }

        $balance = $balanceResponse->json();

        $result['summary']['available_balance'] = $balance['remaining_days'];
        $result['summary']['remaining_balance'] = $balance['remaining_days'] - $result['summary']['deduct_days'];
        //  [
        //     'requested_days'    => $result['requested_days'],
        //     'working_days'      => $result['working_days'],
        //     'paid_days'         => $result['paid_days'],
        //     'balance_days'      => $result['balance_days'],
        //     'unpaid_days'       => $result['unpaid_days'],
        //     'deduct_days'       => $result['deduct_days'],
        //     'available_balance' => $balance['remaining_days'],
        //     'remaining_balance' => $balance['remaining_days'] - $result['deduct_days'],
        // ];

        return response()->json($result);
    }
}