<?php

namespace App\Services\Absence;

use App\DTO\LeaveCalculationRequest;
use App\Models\Misc\Document;
use App\Services\Absence\AbsenceService;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use Illuminate\Support\Facades\DB;

class AbsenceDocumentHandler implements DocumentTypeHandlerInterface
{
    protected AbsenceService $absenceService;
    protected LeaveCalculatorService $calculator;
    protected LeaveDayGeneratorService $generator;

    public function __construct(
    AbsenceService $absenceService,
    LeaveCalculatorService $calculator,
    LeaveDayGeneratorService $generator
) {

    $this->absenceService = $absenceService;
    $this->calculator = $calculator;
    $this->generator = $generator;
}

    public function create(
        Document $document,
        array $data
    )
    {

            try {

            DB::beginTransaction();



       $absence = $this->absenceService->create(
            $document,
            $data
        );

        $request = new LeaveCalculationRequest([

        'leave_type_id' => $absence->leave_type_id,

        'start_date' => $absence->departure_date,

        'end_date' => $absence->return_date,

        'start_time' => $absence->departure_time,

        'end_time' => $absence->return_time,

        'employee_id' => $document->actor_id,

    ]);

    $simulation = $this->calculator->calculate(
        $request
    );

    
    
    $this->generator->generate(
        $absence,
        $simulation
        );
        
        
        // throw new \Exception(json_encode($simulation), 1);
    
            DB::commit();
                
            
            } catch (\Throwable $th) {
                DB::rollback();
                throw $th;
            }

    

    }

   
}