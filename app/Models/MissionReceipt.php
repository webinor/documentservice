<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MissionReceipt extends Model
{
    protected $table = 'mission_receipts';

    protected $fillable = [
        'code',
        'mission_id',
        'name',
        'reference',
        'receipt_date',
        'description',
    ];

    protected $casts = [
        'receipt_date' => 'date:Y-m-d',
    ];

    /**
     * Mission propriétaire du justificatif.
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    /**
     * Dépenses couvertes par ce justificatif.
     */
    public function expenses(): BelongsToMany
    {
        return $this->belongsToMany(
            MissionExpense::class,
            'mission_expense_receipt',
            'mission_receipt_id',
            'mission_expense_id'
        )
        ->withPivot('allocated_amount')
        ->withTimestamps();
    }

    /**
     * Tous les fichiers associés.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    /**
     * Fichier principal du justificatif.
     */
    public function file(): MorphOne
    {
        return $this->morphOne(File::class, 'model')
            ->where('type', 'RECEIPT');
    }

    /**
     * Total affecté aux dépenses.
     */
    // public function getAllocatedAmountAttribute(): float
    // {
    //     return (float) $this->expenses()
    //         ->sum('mission_expense_receipt.allocated_amount');
    // }

    public function getAllocatedAmountAttribute(): float
{
    if ($this->relationLoaded('expenses')) {
        return (float) $this->expenses->sum(function ($expense) {
            return (float) $expense->pivot->allocated_amount;
        });
    }

    return (float) $this->expenses()
        ->sum('mission_expense_receipt.allocated_amount');
}

    public function hasReceiptFile(): bool
    {
        return $this->file()->exists();
    }
}