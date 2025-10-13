<?php

namespace App\Models\Misc;

use App\Models\Finance\InvoiceProvider;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'document_type_id',
        'department_id',
        'workflow_id',
        'created_by',
        'reference',
        'folder_id', // 🆕 on ajoute folder_id
    ];

    

    /**
     * 🔁 Dossier parent du document
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }


    /**
     * Get the document_type that owns the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document_type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class,);
    }

    /**
     * Get the invoice_provider associated with the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function invoice_provider(): HasOne
    {
        return $this->hasOne(InvoiceProvider::class, );
    }

    public function getCreatedAtAttribute($value)
    {
        if (!$value ) {
            return null; // ou return '';
        }
        return \Carbon\Carbon::parse($value)->format('d-m-Y'); 
    }

    /**
     * Get the attachment associated with the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get the secondary_attachments associated with the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function secondary_attachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->whereIsMain(false);
    }

     public function main_attachment(): HasOne
    {
        return $this->hasOne(Attachment::class,)->where('is_main', true);
    }

    public function contrat()
{
    //return $this->hasOne(Contrat::class);
}

public function demandeConge()
{
   // return $this->hasOne(DemandeConge::class);
}

public function getActeurPrincipalAttribute()
{
    switch ($this->document_type->slug) {
        case 'facture-fournisseur':
            return $this->invoice_provider ? $this->invoice_provider->provider : null;
        case 'CONTRAT':
            return $this->contrat?$this->contrat->employe: null;
        case 'CONGE':
          //  return $this->demandeConge?->demandeur;
        default:
            return null;
        }

}

}
