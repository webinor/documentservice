<?php

namespace App\Models;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
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


public function allowances()
{
    return $this->hasMany(MissionAllowance::class);
}

        public function getStartDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}

       public function getEndDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}


    public function getPaymentRecipient(): int
    {
        return $this->actor_id;
    }

    public function getPaymentAmount(): float
    {
        return $this->calculateSettlementAmount();
    }

    public function getPaymentReason(): string
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
}
