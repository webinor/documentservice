<?php

namespace App\Services\Mission;

use App\DTOs\MissionFinancialReportDto;
use App\Models\Mission;
// use App\DTOs\MissionFinancialReportDto;

class MissionFinancialReportService
{

        private MissionExpenseService $expenseService;
        private MissionAllowanceService $allowanceService;
        private MissionAdvanceService $advanceService;
        private MissionRegulationService $regulationService;

    public function __construct(  
        
         MissionExpenseService $expenseService,
         MissionAllowanceService $allowanceService,
         MissionAdvanceService $advanceService,
         MissionRegulationService $regulationService

         ) {
        $this->expenseService = $expenseService;
        $this->allowanceService = $allowanceService;
        $this->advanceService = $advanceService;
        $this->regulationService = $regulationService;
    }
    public function generate(Mission $mission)
    {
        $expenseReport   =  $this->expenseService->calculate($mission);

        $allowanceReport =  $this->allowanceService->calculate($mission);

        $advanceReport   =  $this->advanceService->calculate($mission);

        $regulationReport =
    $this->regulationService->calculate(
        $mission
    );

        $realCost = 
            $expenseReport['total_reel']
            + $allowanceReport['total_final'];

        $totalAdvances =$advanceReport['total'];

        $balance = $totalAdvances - $realCost;

        return [
            'mission' => [
                'id' => $mission->id,
                'code' => $mission->code,
            ],

            'expenses' => $expenseReport,

            'allowances' => $allowanceReport,

            'advances' => $advanceReport,

            'regulations' => $regulationReport,

            'financial_summary' => [
                'real_cost' => $realCost,
                'total_advances' => $totalAdvances,

                'balance' => $balance,

                'status' =>
                    $balance >= 0
                        ? 'SURPLUS'
                        : 'DEFICIT',
            ],
        ];
    }
}