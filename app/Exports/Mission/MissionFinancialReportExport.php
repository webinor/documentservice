<?php

namespace App\Exports\Mission;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MissionFinancialReportExport implements WithMultipleSheets
{
    private $report;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function sheets(): array
    {
        return [
            new MissionSummarySheet($this->report),

            new MissionExpensesSheet(
                $this->report['expenses']
            ),

            new MissionAllowancesSheet(
                $this->report['allowances']
            ),

            new MissionAdvancesSheet(
                $this->report['advances']
            ),

            new MissionRegulationsSheet(
                $this->report['regulations'] ?? []
            ),
        ];
    }
}