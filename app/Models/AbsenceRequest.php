<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsenceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
           'reason' ,
                'beneficiary',
                'departure_date',
                'departure_time',
                'return_date',
                'return_time',
                'duties_handover',
                'handover_details',
                'document_id',
    ];


        public function getDepartureDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}

       public function getReturnDateAttribute($value)
{
    if (!$value ) {
        return null; // ou return '';
    }
    return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
}
}
