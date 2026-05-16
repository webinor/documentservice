<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategoryRule extends Model
{
    use HasFactory;

    public function category()
{
    return $this->belongsTo(ExpenseCategory::class);
}
}
