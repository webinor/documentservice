<?php

namespace App\Services;

use App\Models\Misc\Document;
use Carbon\Carbon;

class DocumentFilterService
{
    /**
     * CONFIGURATION DES TYPES DE DOCUMENTS
     * ------------------------------------
     * Pour chaque type :
     * - relation = relation Eloquent principale
     * - fields = champs dynamiques à retourner
     * - filters = filtres autorisés selon ce document
     */
    protected array $documentTypes = [
        "invoice" => [
            "relation" => "invoice_provider",
            "fields" => [
                "amount" => "amount",
                "actor"  => "provider",
            ],
            "filters" => [
                "amount" => true,
                "fournisseur_id" => true,
            ],
        ],

        "taxi" => [
            "relation" => "taxi_provider",
            "fields" => [
                "amount" => "cost",
                "actor"  => "driver_name",
            ],
            "filters" => [
                "driver_id" => true,
            ],
        ],

        "mission" => [
            "relation" => "mission_provider",
            "fields" => [
                "amount" => "total_cost",
                "actor"  => "mission_lead",
            ],
            "filters" => [
                "employee_id" => true,
            ],
        ],
    ];

    /**
     * Appliquer les filtres + mapping dynamique
     */
    public function filter(array $filters)
    {
        $query = Document::query()->with("document_type");

        /**
         * 1) Déterminer le type de document + chargement de la config
         */
        $typeKey = $filters["document_type_key"] ?? null;

        if (!$typeKey || !isset($this->documentTypes[$typeKey])) {
            return ["error" => "Document type invalid or missing"];
        }

        $config = $this->documentTypes[$typeKey];
        $relation = $config["relation"];
        $fields = $config["fields"];
        $allowedFilters = $config["filters"];

        /**
         * 2) Filtres généraux
         */
        if (!empty($filters["status"])) {
            $statuses = is_array($filters["status"])
                ? $filters["status"]
                : explode(",", $filters["status"]);
            $query->whereIn("status", $statuses);
        }

        if (!empty($filters["document_type_id"])) {
            $query->where("document_type_id", $filters["document_type_id"]);
        }

        /**
         * 3) Filtres dynamiques selon le documentType
         * -------------------------------------------
         */

        // Filtre fournisseur
        if (!empty($filters["fournisseur_id"]) && !empty($allowedFilters["fournisseur_id"])) {
            $query->whereHas($relation, function ($q) use ($filters) {
                $q->where("id", $filters["fournisseur_id"]);
            });
        }

        // Filtre montant dynamique
        if (!empty($filters["amount"]) && !empty($allowedFilters["amount"])) {
            $amountField = $fields["amount"];

            $query->whereHas($relation, function ($q) use ($filters, $amountField) {
                switch ($filters["amount"]) {
                    case "lt_100k":
                        $q->where($amountField, "<", 100000);
                        break;
                    case "100k_500k":
                        $q->whereBetween($amountField, [100000, 500000]);
                        break;
                    case "gt_500k":
                        $q->where($amountField, ">", 500000);
                        break;
                }
            });
        }

        // Exemple pour Taxi / Mission
        if (!empty($filters["driver_id"]) && !empty($allowedFilters["driver_id"])) {
            $query->whereHas($relation, function ($q) use ($filters) {
                $q->where("driver_id", $filters["driver_id"]);
            });
        }

        if (!empty($filters["employee_id"]) && !empty($allowedFilters["employee_id"])) {
            $query->whereHas($relation, function ($q) use ($filters) {
                $q->where("employee_id", $filters["employee_id"]);
            });
        }

        /**
         * 4) Filtres par dates
         */
        if (!empty($filters["date_start"])) {
            $filters["date_start"] = Carbon::parse($filters["date_start"])->format("Y-m-d");
        }

        if (!empty($filters["date_end"])) {
            $filters["date_end"] = Carbon::parse($filters["date_end"])->format("Y-m-d");
        }

        if (!empty($filters["date_start"]) && !empty($filters["date_end"])) {
            $query->whereBetween("created_at", [
                $filters["date_start"],
                $filters["date_end"],
            ]);
        } elseif (!empty($filters["date_start"])) {
            $query->whereDate("created_at", ">=", $filters["date_start"]);
        } elseif (!empty($filters["date_end"])) {
            $query->whereDate("created_at", "<=", $filters["date_end"]);
        }

        /**
         * 5) Charger la relation principale dynamiquement
         */
        $query->with($relation);

        /**
         * 6) Mapping dynamique du résultat
         */
        $documents = $query->get()->map(function ($doc) use ($relation, $fields) {
            $provider = $doc->{$relation};

            return [
                "id" => $doc->id,
                "title" => $doc->title,
                "document_type_name" => $doc->document_type->name,
                "document_type_id" => $doc->document_type_id,
                "type" => $doc->document_type->name,
                "status" => $doc->status,

                // Champs dynamiques
                "amount" => $provider->{$fields["amount"]} ?? null,
                "acteur_principal" => $provider->{$fields["actor"]} ?? null,

                "created_at" => $doc->created_at,
                "created_by" => $doc->created_by,
            ];
        });

        return $documents;
    }
}
