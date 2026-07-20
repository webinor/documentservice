<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use App\Services\Mission\MissionAllowanceCalculator;
use App\Services\Mission\MissionExpenseAmountService;
use App\Services\Mission\MissionExpenseCalculatorService;
use App\Services\Mission\MissionExpenseService;
use App\Services\Transaction\TransactionTypeLabelService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model implements PayableDocumentInterface
{
    use HasFactory;

    protected $fillable = [
        "destination",
        "code",
        "start_date",
        "end_date",
        "estimated_budget",
        "advance_amount",
        "is_special",
        "actor_type",
        "actor_id",
        "document_id",
        "scope",
        /**
         * ==========================================
         * 🧭 BASE (départ/retour du siège)
         * ==========================================
         */

        "departure_date_base_planned",
        "departure_time_base_planned",

        "arrival_date_base_planned",
        "arrival_time_base_planned",

        "departure_date_base_actual",
        "departure_time_base_actual",

        "arrival_date_base_actual",
        "arrival_time_base_actual",

        /**
         * ==========================================
         * 🏗 SITE (départ/retour intervention)
         * ==========================================
         */

        "departure_date_site_planned",
        "departure_time_site_planned",

        "arrival_date_site_planned",
        "arrival_time_site_planned",

        "departure_date_site_actual",
        "departure_time_site_actual",

        "arrival_date_site_actual",
        "arrival_time_site_actual",
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get all of the missions_expenses for the Mission
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mission_expenses(): HasMany
    {
        return $this->hasMany(MissionExpense::class);
    }

    public function getDurationDaysAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return Carbon::parse($this->start_date)->diffInDays(
            Carbon::parse($this->end_date)
        ) + 1;
    }

    public function allowances()
    {
        return $this->hasMany(MissionAllowance::class);
    }

    public function advances()
    {
        return $this->hasMany(MissionAdvance::class);
    }

   public function financialTransactions()
    {
        return $this->morphMany(FinancialTransaction::class, 'transactable');
    }

    public function getStartDatAttribute($value)
    {
        if (!$value) {
            return null; // ou return '';
        }
        return Carbon::parse($value)->format("d-m-Y");
    }

    public function getEndDatAttribute($value)
    {
        if (!$value) {
            return null; // ou return '';
        }
        return Carbon::parse($value)->format("d-m-Y");
    }

    public function getSettlementActor(): array
    {
        return ['actor_type' => $this->document->actor_type , 'actor_id' => $this->document->actor_id];
    }

    public function getSettlementAmount(string $transaction_type_code): float
    {
        return $this->calculateSettlementAmount($transaction_type_code);
    }

    public function getSettlementDirection(
        string $transaction_type_code
    ): string {


     switch ($transaction_type_code) {

  

        case 'MISSION_EXPENSE_ADVANCE':

            return "OUT"; 
            break;
    }



        $balance = $this->calculateSettlementAmount($transaction_type_code);

        if ($balance > 0) {
            return "OUT";
        }

        if ($balance < 0) {
            return "IN";
        }

        return "NONE";
    }

    public function getSettlementDetails(): array
    {
        return [
            "document_type" => "mission",

            "reference" => $this->reference,

            "mission" => [
                "id" => $this->id,
                "reference" => $this->reference,
                "title" => $this->title,
                "purpose" => $this->purpose,
                "destination" => $this->destination,
                "departure_date" => $this->departure_date,
                "return_date" => $this->return_date,
                "status" => $this->status,
            ],

            "missionnaire" => [
                "id" => $this->actor_type == "INTERNAL" ? $this->actor_id : 0, //////////REVOIR LE CAS OU C'EST EXTERNAL
                "type" => $this->actor_type ?? "--",
                "name" => $this->missionary->name ?? "--",
                "email" => $this->missionary->email ?? "--",
                "phone" => $this->missionary->phone ?? "--",
            ],
        ];
    }

    public function createSettlementRecord(
    string $transactionTypeCode,
    string $transactionCode
): void {


    $direction = $this->getSettlementDirection($transactionTypeCode);
    $amount = $this->getSettlementAmount($transactionTypeCode);

    switch ($transactionTypeCode) {

  

        case 'MISSION_EXPENSE_ADVANCE':

            $this->financialTransactions()->firstOrCreate(
                [
                    'transaction_code' => $transactionCode,
                ],
                [
                    'amount' => $amount,
                    'status' => 'PENDING',
                    'transaction_type_code' => $transactionTypeCode,
                    'type' => 'ADVANCE',
                    'adjustment_type' => 'NONE',
                    'direction' => $direction,
                ]
            );

            break;

        case 'MISSION_SETTLEMENT':

        

            $this->financialTransactions()->firstOrCreate(
                [
                    'transaction_code' => $transactionCode,
                ],
                [
                    'status' => 'PENDING',
                    'transaction_type_code' => $transactionTypeCode,
                    'type' => 'SETTLEMENT',
                    'adjustment_type' => $direction === 'OUT'
                        ? 'SUPPLEMENT'
                        : 'REFUND',
                    'direction' => $direction,
                    'amount' => abs($amount),
                ]
            );

            break;
    }
}

    public function getSettlementReason(string $transaction_type_code): string
    {
        $transactionTypeLabelService = new TransactionTypeLabelService();
        return $transactionTypeLabelService->getLabel($transaction_type_code); //;// "Régularisation mission";
    }

    public function calculateSettlementAmount(
        string $transaction_type_code
    ): float {

    // throw new Exception("Error Processing Request", 1);
    
        if ($transaction_type_code == "MISSION_EXPENSE_ADVANCE") {
            $service = new MissionExpenseAmountService(
                new MissionExpenseCalculatorService()
            );

            $totals = $service->calculateTotals($this);

            return $totals["planned_total"];
        }

        // Total des dépenses réelles
        // $totalRealExpenses = $this->mission_expenses()
        //     ->where("type", "DECLAREE")
        //     ->sum("amount");

        $expenses = app(MissionExpenseService::class)->calculate($this);

        $totalRealExpenses = $expenses["total_reel"];

        $totalAllowances = $this->allowances()
        ->where("calculation_stage", "FINAL")
        ->sum("total");

        $totalAdvances = $this->financialTransactions()->whereType("ADVANCE")->sum('amount');

        /**
         * Résultat :
         *
         * > 0  => la caisse doit payer le missionnaire
         * < 0  => le missionnaire doit rembourser
         * = 0  => équilibré
         */
        return ((float) ($totalRealExpenses + $totalAllowances - $totalAdvances));
    }

    public function simulate($missionId)
    {
        $mission = Mission::findOrFail($missionId);

        $employees = $mission->participants;

        $calculator = new MissionAllowanceCalculator();

        $results = [];

        foreach ($employees as $employee) {
            $results[] = [
                "employee_id" => $employee->id,
                "amount" => $calculator->calculate($mission, $employee),
            ];
        }

        return $results;
    }
}
