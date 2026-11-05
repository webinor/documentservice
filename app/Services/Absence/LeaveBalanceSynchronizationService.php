<?php

namespace App\Services\Absence;

use App\Models\Misc\Document;
use App\Services\EmployeeServiceClient;

class LeaveBalanceSynchronizationService
{

protected EmployeeServiceClient $employee_service_client;

public function __construct(EmployeeServiceClient $employee_service_client) {
    $this->employee_service_client = $employee_service_client;
}

public function deductFromWorkflow(
    string $documentUuid,
    int $instanceId
) {
    $document = Document::with([
        'absence_request.leaveRequestDays'
    ])->whereUuid($documentUuid)->firstOrFail();

    $absence = $document->absence_request;

    /*
     * Récupérer uniquement les jours réellement déductibles.
     *
     * On conserve la date de chaque jour pour permettre
     * au microservice Employé de déterminer le cycle concerné.
     */
    $dailyDeductions = $absence->leaveRequestDays
        ->filter(function ($day) {
            return (bool) $day->deducts_balance
                && (float) $day->deduct_days > 0;
        })
        ->map(function ($day) {
            return [
                'date' => $day->date,
                'days' => (float) $day->deduct_days,
            ];
        })
        ->values()
        ->toArray();

    if (empty($dailyDeductions)) {
        return [
            'success' => true,
            'message' => 'Aucun jour à déduire.',
        ];
    }

    $days = array_sum(array_column(
        $dailyDeductions,
        'days'
    ));

    // throw new \Exception(json_encode($dailyDeductions), 1);


    $employeeId = $document->actor_id;

    return $this->employee_service_client->deductLeaveDays([
        'employee_id' => $employeeId,
        'absence_request_id' => $absence->id,
        'document_uuid' => $documentUuid,
        'workflow_instance_id' => $instanceId,
        'leave_type_id' => $absence->leave_type_id,

        // Conservation du total pour faciliter les contrôles.
        'days' => $days,

        // Détail indispensable pour répartir les débits.
        'daily_deductions' => $dailyDeductions,
    ]);
}
    public function OlddeductFromWorkflow(
        string $documentUuid,
        int $instanceId
    )
    {
        $document = Document::with([
        'absence_request.leaveRequestDays'
    ])->whereUuid($documentUuid)->firstOrFail();




    $absence = $document->absence_request;

        // throw new \Exception(json_encode($absence), 1);


    $days = $absence->leaveRequestDays()
        ->sum('deduct_days');

        // throw new \Exception(json_encode($document), 1);


    if ($days <= 0) {
    return [
        'success' => true,
        'message' => 'Aucun jour à déduire.'
    ];
}



    $employeeId = $document->actor_id;

    return $this->employee_service_client->deductLeaveDays([
        'employee_id' => $employeeId,
        'absence_request_id' => $absence->id,
        'document_uuid' => $documentUuid,
        'workflow_instance_id' => $instanceId,
        'leave_type_id' => $absence->leave_type_id,
        'days' => $days,
    ]);
    }
}