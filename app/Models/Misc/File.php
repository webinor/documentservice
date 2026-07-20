<?php

namespace App\Models\Misc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;


    protected $fillable = [
        'path',
        'size',
        'type',
    ];


    public function model()
    {
        return $this->morphTo();
    }
}