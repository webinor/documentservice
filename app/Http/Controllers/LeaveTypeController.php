<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Services\Absence\LeaveTypeService;
use Illuminate\Http\JsonResponse;

class LeaveTypeController extends Controller
{
        protected LeaveTypeService $leaveTypeService;

    public function __construct(
         LeaveTypeService $leaveTypeService
    ) {
        $this->leaveTypeService = $leaveTypeService;
    }

    /**
     * Liste des types de congés.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            $this->leaveTypeService->all()
        );
    }
}