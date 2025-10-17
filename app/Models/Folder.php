<?php

namespace App\Models;

use App\Models\Misc\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "description",
        "parent_id",
        "created_by",
        "notify_allowed_user", // ✅ nouveau champ ajouté
    ];

    /**
     * Casts automatiques pour certains attributs.
     */
    protected $casts = [
        "notify_allowed_user" => "boolean",
    ];

    /**
     * 🔁 Relation : un dossier peut contenir plusieurs documents
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * 🔁 Relation : dossier parent (si dossiers imbriqués)
     */
    public function parent()
    {
        return $this->belongsTo(Folder::class, "parent_id");
    }

    /**
     * 🔁 Relation : sous-dossiers
     */
    public function children()
    {
        return $this->hasMany(Folder::class, "parent_id");
    }

    /**
     * Get all of the department_folders for the Folder
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function department_folders(): HasMany
    {
        return $this->hasMany(DepartmentFolder::class);
    }

    public function getCreatedAtAttribute($value)
    {
        if (!$value) {
            return null; // ou return '';
        }
        return \Carbon\Carbon::parse($value)->format("d-m-Y");
    }

    /**
     * Retourne le chemin complet du dossier, ex: /Racine/Impots/2024
     */
    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path . "/" . $this->name;
        }
        return "/" . $this->name; // dossier racine
    }

    public function getPathSegments()
    {
        $pathSegments = [];
        $currentFolder = $this;
        while ($currentFolder) {
            array_unshift($pathSegments, [
                "id" => $currentFolder->id,
                "name" => $currentFolder->name,
            ]);
            $currentFolder = $currentFolder->parent ?? null;
        }

        return $pathSegments;
    }
}
