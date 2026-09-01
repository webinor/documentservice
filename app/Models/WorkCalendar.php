<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCalendar extends Model
{
    use HasFactory;

    public function rules(): HasMany
{
    return $this->hasMany(
        WorkCalendarRule::class
    );
}
}
