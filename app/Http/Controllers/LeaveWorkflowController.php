<?php

namespace App\Http\Controllers;

use App\Services\Absence\LeaveBalanceSynchronizationService;
use Illuminate\Http\Request;

class LeaveWorkflowController extends Controller
{
    public function deductFromWorkflow(
        Request $request,
        LeaveBalanceSynchronizationService $service
    ) {
        return response()->json(
            $service->deductFromWorkflow(
                $request->document_id,
                $request->instance_id
            )
        );
    }
}
