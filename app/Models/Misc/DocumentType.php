<?php

namespace App\Models\Misc;

use App\Models\DepartmentDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class DocumentType extends Model
{
    use HasFactory;


    protected $fillable = [ 
    "code",
    "name" ,
    "reception_mode" 
    ];

    /**
     * Get all of the department_document_types for the DocumentType
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function department_document_types(): HasMany
    {
        return $this->hasMany(DepartmentDocumentType::class,);
    }

    public function getDepartmentIds()
    {
        return DepartmentDocumentType::
            where("document_type_id", $this->id)
            ->pluck("department_id")
            ->toArray();
    }


}
