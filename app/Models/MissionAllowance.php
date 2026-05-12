<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissionAllowance extends Model
{
    use HasFactory;


    public function type()
{
    return $this->belongsTo(AllowanceType::class, 'allowance_type_id');
}

public function mission()
{
    return $this->belongsTo(Mission::class);
}
}
