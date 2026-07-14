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
        int $documentId,
        int $instanceId
    )
    {
        $document = Document::with([
        'absence_request.leaveRequestDays'
    ])->findOrFail($documentId);




    $absence = $document->absence_request;

    $days = $absence->leaveRequestDays()
        ->sum('deduct_days');

        // throw new \Exception(json_encode($days), 1);


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
        'document_id' => $documentId,
        'workflow_instance_id' => $instanceId,
        'leave_type_id' => $absence->leave_type_id,
        'days' => $days,
    ]);
    }
}