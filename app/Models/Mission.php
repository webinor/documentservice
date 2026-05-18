<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use App\Services\Mission\MissionAllowanceCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model implements PayableDocumentInterface
{
    use HasFactory;

    protected $fillable = [
        'destination',
        'start_date',
        'end_date',
        'estimated_budget',
        'advance_amount',
        'is_special',
        'actor_type',
        'actor_id',
        'document_id',
        'scope',
            'start_date_planned' ,
            'end_date_planned' ,

            'start_date_actual' ,
            'end_date_actual' ,

            'departure_time_planned',
            'return_time_planned',

            'departure_time_actual',
            'return_time_actual',

      
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

    return Carbon::parse($this->start_date)
        ->diffInDays(
            Carbon::parse($this->end_date)
        ) + 1;
}


public function allowances()
{
    return $this->hasMany(MissionAllowance::class);
}

        public function getStartDatAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return Carbon::parse($value)->format('d-m-Y'); 
}

       public function getEndDatAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return Carbon::parse($value)->format('d-m-Y'); 
}


    public function getSettlementActor(): int
    {
        return $this->actor_id;
    }

    public function getSettlementAmount(): float
    {
        return $this->calculateSettlementAmount();
    }

    public function getSettlementDirection(): string
{
    $balance = $this->calculateSettlementAmount();

    if ($balance > 0) {
        return 'OUT';
    }

    if ($balance < 0) {
        return 'IN';
    }

    return 'NONE';
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
            "id" => $this->actor_type == "INTERNAL" ?  $this->actor_id : 0, //////////REVOIR LE CAS OU C'EST EXTERNAL
            "type" => $this->actor_type ?? "--",
            "name" => $this->missionary->name ?? "--",
            "email" => $this->missionary->email ?? "--",
            "phone" => $this->missionary->phone ?? "--",
        ],

        // "department" => [
        //     "id" => $this->department->id,
        //     "name" => $this->department->name,
        // ],

        // "financial" => [
        //     "currency" => $this->currency ?? "XAF",

        //     "advance_amount" => $this->advance_amount,

        //     "real_expense_amount" => $this->real_expense_amount,

        //     "settlement_amount" => $this->settlement_amount,

        //     "remaining_amount" => $this->remaining_amount,
        // ],

        // "workflow" => [
        //     "current_step" => $this->current_step,
        //     "validated_at" => $this->validated_at,
        // ],

        // "metadata" => [
        //     "generated_at" => now()->toDateTimeString(),
        //     "service" => "mission-service",
        // ]
    ];
}

    public function getSettlementReason(): string
    {
        return "Régularisation mission";
    }

    public function calculateSettlementAmount(): float
{
    // Total des dépenses réelles
    $totalRealExpenses = $this->mission_expenses()
        ->where('type', 'DECLAREE')
        ->sum('amount');

    $totalAllowances = $this->allowances()
        ->sum('total');

    $totalAdvances = $this->advance_amount;//advances()->sum('amount');

 


    /**
     * Résultat :
     *
     * > 0  => la caisse doit payer le missionnaire
     * < 0  => le missionnaire doit rembourser
     * = 0  => équilibré
     */
      return (float) (
        ($totalRealExpenses + $totalAllowances)
        - $totalAdvances
    );
}

public function simulate($missionId)
{
    $mission = Mission::findOrFail($missionId);

    $employees = $mission->participants;

    $calculator = new MissionAllowanceCalculator();

    $results = [];

    foreach ($employees as $employee) {

        $results[] = [
            'employee_id' => $employee->id,
            'amount' => $calculator->calculate($mission, $employee)
        ];
    }

    return $results;
}
}
