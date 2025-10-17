<?php

namespace App\Models\Misc;

use App\Models\Finance\InvoiceProvider;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Http;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        "title",
        "document_type_id",
        "department_id",
        "workflow_id",
        "created_by",
        "reference",
        "folder_id", // 🆕 on ajoute folder_id
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
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Get the invoice_provider associated with the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function invoice_provider(): HasOne
    {
        return $this->hasOne(InvoiceProvider::class);
    }

    public function getCreatedAtAttribute($value)
    {
        if (!$value) {
            return null; // ou return '';
        }
        return \Carbon\Carbon::parse($value)->format("d-m-Y");
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
        return $this->hasOne(Attachment::class)->where("is_main", true);
    }

    public function contrat()
    {
        //return $this->hasOne(Contrat::class);
    }

    public function creator()
    {
        if ($this->created_by) {
            $response = Http::withToken(
                config("services.user_service.base_url")
            )->get(
                config("services.user_service.base_url") .
                    "/{$this->created_by}"
            );

            if ($response->ok()) {
                $creatorData = $response->json();
                return $creator = $creatorData["user"]["name"] ?? null;
            }

            return "inconnu";
        }
    }

    public function userCan($token, $user, $document, $action)
    {
        // 🔹 Vérifier la permission via microservice
        $permissionServiceUrl =
            config("services.user_service.base_url") . "/permissions/check";
        $permissionResponse = Http::withToken($token)->get(
            $permissionServiceUrl,
            [
                "userId" => $user["id"],
                "resourceType" => "document_type",
                "resourceId" => $document->document_type_id,
                "action" => $action,
                "folderId" => $document->folder_id,
            ]
        );

        if (
            !$permissionResponse->ok() ||
            !$permissionResponse->json("allowed")
        ) {
            return false;

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Vous n'avez pas la permission de consulter ce document.",
                ],
                403
            );
        }

        return $permissionResponse["allowed"];
    }

    public function demandeConge()
    {
        // return $this->hasOne(DemandeConge::class);
    }

    public function getActeurPrincipalAttribute()
    {
        switch ($this->document_type->slug) {
            case "facture-fournisseur":
                return $this->invoice_provider
                    ? $this->invoice_provider->provider
                    : null;
            case "CONTRAT":
                return $this->contrat ? $this->contrat->employe : null;
            case "CONGE":
            //  return $this->demandeConge?->demandeur;
            default:
                return null;
        }
    }
}
