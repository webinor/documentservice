<?php

namespace App\DTO;


class LeaveCalculationRequest
{
    public int $leaveTypeId;

    public string $startDate;

    public string $endDate;

    public ?string $startTime;

    public ?string $endTime;

    public ?int $employeeId;


    public function __construct(array $data)
    {
        $this->leaveTypeId = $data['leave_type_id'];

        $this->startDate = $data['start_date'];

        $this->endDate = $data['end_date'];

        $this->startTime = $data['start_time'] ?? null;

        $this->endTime = $data['end_time'] ?? null;

        $this->employeeId = $data['employee_id'] ?? null;
    }
}