<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Mission\MissionAdvanceService;
use App\Services\Mission\MissionRegulationService;
use App\Services\TaxiPaper\TaxiPaperService;
use Exception;

class SettlementController extends Controller
{
    private $advanceService;
    private $regulationService;
    private $taxiPaperService;

    public function __construct(
        MissionAdvanceService $advanceService,
        MissionRegulationService $regulationService,
        TaxiPaperService $taxiPaperService
    ) {
        $this->advanceService = $advanceService;
        $this->regulationService = $regulationService;
        $this->taxiPaperService = $taxiPaperService;
    }

    public function markAsPaid(Request $request)
    {
        $request->validate([
            'transaction_code' => 'required|string',
            'transaction_type_code' => 'required|string',
            'document_id' => 'required|integer',
            'amount' => 'required',
            'direction' => 'required|string',
        ]);

        /*
        const LABELS = [

        'MISSION_EXPENSE_ADVANCE'
            => 'Avance sur frais de mission',

        'MISSION_SETTLEMENT'
            => 'Régularisation mission',

        'MISSION_REIMBURSEMENT'
            => 'Remboursement frais mission',

        'MISSION_REFUND'
            => 'Remboursement entreprise',

    ];
        */

        try {

            $type = $request->transaction_type_code;

            if ($type === 'MISSION_EXPENSE_ADVANCE') {
                // return $this->advanceService->markAsPaid($request->all());
            }

            if ($type === 'MISSION_SETTLEMENT') {
                // return $this->regulationService->markAsPaid($request->all());
            }

            if ($type === 'TAXI_PAPER_SETTLEMENT') {
                // return $this->taxiPaperService->markAsPaid($request->all());
            }

            if ($type === 'FEE_NOTE_SETTLEMENT') {
                // return $this->feeNoteService->markAsPaid($request->all());
            }

            return $this->regulationService->markAsPaid($request->all());


            throw new Exception("Unsupported document type: " . $type);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}