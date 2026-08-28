<?php

namespace App\Models;

use App\Models\Misc\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileSignature extends Model
{
    use HasFactory;



protected $fillable = [

    'file_id',
    'user_id',

    'page',

    'x',
    'y',

    'width',
    'height',

    // 'signed_path',

    'signed_at'

];




protected $casts = [

    'signed_at'=>'datetime'

];


public function file()
{
    return $this->belongsTo(File::class);
}
}
