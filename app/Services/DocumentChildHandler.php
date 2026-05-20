<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;

class DocumentChildHandler
{
    /**
     * Gère la création et l’association d’un document enfant
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  object  $documentType  Objet contenant class_name, relation_name et type
     * @param  array  $validated  Données validées du formulaire
     * @return \Illuminate\Database\Eloquent\Model  L'enfant créé
     * @throws \Exception
     */
    public function handle($parentModel, $documentType, array $validated)
    {

            // Récupère les classes et relations séparées par des points
            $classNames = explode('.', $documentType->class_name);   // Ex: ["App\Models\Finance\InvoiceProvider", "App\Models\Finance\ItSupplier"]
            $relationNames = explode('.', $documentType->relation_name); // Ex: ["invoice_provider", "it_supplier"]

            // Vérification : le nombre de classes et de relations doit correspondre
            if (count($classNames) !== count($relationNames)) {
                throw new \Exception("Le nombre de classes et de relations ne correspond pas !");
            }

            // Mapper les données selon le type
            $type = $documentType->slug ?? null;
            $mapData = $this->mapData($type, $validated , $parentModel);
            $data = $mapData["data"];
            $relations = $mapData["relations"];

            // throw new Exception(json_encode($data ,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 1);
            

            // Parent actuel pour la relation
            $currentParent = $parentModel;

            foreach ($classNames as $index => $className) {

                $relationName = $relationNames[$index];

                // Vérifie que la classe existe
                if (!class_exists($className)) {
                    throw new \Exception("Classe {$className} introuvable !");
                }

                // Vérifie que la relation existe sur le parent actuel
                if (!method_exists($currentParent, $relationName)) {
                    throw new \Exception("Relation '{$relationName}' introuvable sur " . get_class($currentParent));
                }

                // throw new \Exception(json_encode($relations , JSON_UNESCAPED_UNICODE ));


                // Crée l'instance de l'enfant
                $child = new $className();

                // Remplit les attributs uniquement pour le premier enfant (niveau immédiat)
                if ($index === 0) {
                    // $this->fillModelAttributes($child, $data);
                     if (isset($data['data'])) {
        // 🆕 NOUVEAU FORMAT
        $this->fillModelAttributes($child, $data);
    } else {
        // 🔥 ANCIEN FORMAT
        $this->OldfillModelAttributes($child, $data);
    }
                }

             

                // Sauvegarde via la relation
                $currentParent->$relationName()->save($child);

               


                   if (isset($relations)) {



    foreach ($relations as $currentRelationName => $relationData) {

    if (!method_exists($child, $currentRelationName)) {
        throw new \Exception("Relation {$currentRelationName} introuvable sur " . get_class($child));
    }

    $relationQuery = $child->$currentRelationName();

    $relatedModelClass = get_class($relationQuery->getRelated());

    // =====================================
    // CAS 1 : UNE SEULE ENTITÉ
    // =====================================
    if ( array_is_list($relationData) === false) {

        $relationModel = new $relatedModelClass();

        $relationModel = $this->fillModelAttributesRelation($relationModel, $relationData);

        $relationQuery->save($relationModel);
    }

    // =====================================
    // CAS 2 : COLLECTION (hasMany)
    // =====================================
    else {

        $models = [];

        foreach ($relationData as $item) {

            $relationModel = new $relatedModelClass();

            $relationModel = $this->fillModelAttributesRelation($relationModel, $item);

            $models[] = $relationModel;
        }



        $relationQuery->saveMany($models);
    }
}
}
        // throw new \Exception(json_encode($child));


                // Le nouvel enfant devient le parent pour le prochain niveau
                $currentParent = $child;
            }



    }


    /**
 * Remplit dynamiquement un modèle avec des données en respectant $fillable.
 *
 * @param \Illuminate\Database\Eloquent\Model $model
 * @param array $data
 * @return void
 * @throws \Exception si une clé n'existe pas dans $fillable
 */
function OldfillModelAttributes($model, array $data)
{

                  //  throw new \Exception(json_encode($data));

    foreach ($data as $key => $value) {
        if (in_array($key, $model->getFillable())) {
            // Si la colonne est castée en array (JSON), on stringify si nécessaire
            $casts = $model->getCasts();
            if (isset($casts[$key]) && $casts[$key] === 'array' && is_array($value)) {
                $model->$key = $value; // Eloquent se charge du cast automatiquement
            } else {
                $model->$key = $value;
            }
        } else {
            throw new \Exception("La clé '{$key}' n'existe pas dans les champs fillable de " . get_class($model));
        }
    }
}

/**
 * Remplit dynamiquement un modèle avec des données en respectant $fillable.
 *
 * @param \Illuminate\Database\Eloquent\Model $model
 * @param array $data
 * @return void
 * @throws \Exception
 */
function fillModelAttributes($model, array $data)
{
    /**
     * 🔥 SUPPORT NOUVEAU FORMAT
     * [
     *   "data" => [...],
     *   "relations" => [...]
     * ]
     */
    if (array_key_exists('data', $data) && is_array($data['data'])) {
        $data = $data['data'];
    }

    /**
     * 🔥 IGNORER relations si présent (sécurité)
     */
    unset($data['relations']);

    foreach ($data as $key => $value) {

        if (!in_array($key, $model->getFillable())) {
            throw new \Exception(
                "La clé '{$key}' n'existe pas dans les champs fillable de " . get_class($model)
            );
        }

        $casts = $model->getCasts();

        if (isset($casts[$key]) && $casts[$key] === 'array' && is_array($value)) {
            $model->$key = $value;
        } else {
            $model->$key = $value;
        }
    }
}

/**
 * Remplit un modèle relationnel depuis data["relations"]
 */
private function fillModelAttributesRelation($relationModel, array $data)
{
    if (!isset($data['data'])) {
        // throw new \Exception("Format relation invalide : 'data' manquant");
    }

        // throw new \Exception(json_encode($data));


    $this->fillModelAttributes($relationModel, $data);

    return $relationModel;
}


    /**
     * Mappe les données validées selon le type de document
     */
    private function mapData(?string $type, array $validated , $parentModel = null): array
    {

                 //   throw new \Exception(json_encode($validated));

        $map = [
            'facture-fournisseur-medical' => [
                'amount' => fn($v) => $v['montant'] ?? null,
                'provider' => fn($v) => $v['prestataire'] ?? null,
                'provider_reference' => fn($v) => $v['reference_fournisseur'] ?? null,
                'deposit_date' => fn($v) => isset($v['dateDepot']) ? Carbon::parse($v['dateDepot'])->format('Y-m-d H:i:s') : null,
            ],
            'facture-fournisseur-informatique' => [
                'amount' => fn($v) => $v['montant'] ?? null,
                'provider' => fn($v) => $v['prestataire'] ?? null,
                'provider_reference' => fn($v) => $v['reference_fournisseur'] ?? null,
                'deposit_date' => fn($v) => isset($v['dateDepot']) ? Carbon::parse($v['dateDepot'])->format('Y-m-d H:i:s') : null,
            ],
            'facture-note-honoraire' => [
                'amount' => fn($v) => $v['montant'] ?? null,
                'provider' => fn($v) => $v['prestataire'] ?? null,
                'provider_reference' => fn($v) => $v['reference_fournisseur'] ?? null,
                'deposit_date' => fn($v) => isset($v['dateDepot']) ? Carbon::parse($v['dateDepot'])->format('Y-m-d H:i:s') : null,
            ],
            'facture-prestataires-generaux' => [
                'amount' => fn($v) => $v['montant'] ?? null,
                'provider' => fn($v) => $v['prestataire'] ?? null,
                'provider_reference' => fn($v) => $v['reference_fournisseur'] ?? null,
                'deposit_date' => fn($v) => isset($v['dateDepot']) ? Carbon::parse($v['dateDepot'])->format('Y-m-d H:i:s') : null,
            ],
            'papier-taxi' => [
                'reason' => fn($v) => $v['motif'] ?? null,
                'rides' => fn($v) => $v['trajets'] ?? null,
                'beneficiary' => fn($v) => $v['beneficiaire'] ?? null,
            ],
             'note-de-frais' => [
                'reason' => fn($v) => $v['motif'] ?? null,
                'beneficiary' => fn($v) => $v['beneficiaire'] ?? null,
                'amount' => fn($v) => $v['montant'] ?? null,
            ],
             "demande-d-absence"=>[

                'reason' => fn($v) => $v['motif'] ?? null,
'beneficiary' => fn($v) => $v['beneficiaire'] ?? null,
'departure_date' => fn($v) => isset($v['dateDepart']) 
    ? \Carbon\Carbon::createFromFormat('d-m-Y', $v['dateDepart'])->format('Y-m-d')
    : null,
'departure_time' => fn($v) => isset($v['heureDepart']) 
    ? \Carbon\Carbon::createFromFormat('H:i', $v['heureDepart'])->format('H:i:s')
    : null,
'return_date' => fn($v) => isset($v['dateRetour']) 
    ? \Carbon\Carbon::createFromFormat('d-m-Y', $v['dateRetour'])->format('Y-m-d')
    : null,
'return_time' => fn($v) => isset($v['heureRetour']) 
    ? \Carbon\Carbon::createFromFormat('H:i', $v['heureRetour'])->format('H:i:s')
    : null,


                /*
                'reason' => fn($v) => $v['motif'] ?? null,
                'beneficiary' => fn($v) => $v['beneficiaire'] ?? null,
                'departure_date'=> fn($v) => $v['dateDepart'] ?? null,
                'departure_time'=> fn($v) => $v['dateRetour'] ?? null,
                'return_date'=> fn($v) => $v['heureDepart'] ?? null,
                'return_time'=> fn($v) => $v['heureRetour'] ?? null,
                //'duties_handover'=> fn($v) => $v['duties_handover'] ?? null,
                //'handover_details'=> fn($v) => $v['handover_details'] ?? null,
                */

             ],
      'mission' => [
      
     'data' => [

    /**
     * =========================================
     * INFOS GÉNÉRALES
     * =========================================
     */

    'destination' => fn($v) => $v['destination'] ?? null,
    // 'title' => fn($v) => $v['title'] ?? null,

    'scope' => fn($v) => $v['scope'] ?? null,

    'estimated_budget' => fn($v) => $v['estimated_budget'] ?? 0,
    'advance_amount' => fn($v) => $v['advance_amount'] ?? 0,

    'is_special' => fn($v) => $v['mission_special'] ?? 0,

    /**
     * =========================================
     * ACTOR
     * =========================================
     */

    'actor_id' => fn($v) => $this->resolveActorId($v),
    'actor_type' => fn($v) => $this->resolveActorType($v),

    /**
     * =========================================
     * 🧭 BASE - PLANNED
     * =========================================
     */

    'departure_date_base_planned' => fn($v) =>
        $this->toDate($v['departure_date_base_planned'] ?? null),

    'departure_time_base_planned' => fn($v) =>
        $v['departure_time_base_planned'] ?? null,

    'arrival_date_base_planned' => fn($v) =>
        $this->toDate($v['arrival_date_base_planned'] ?? null),

    'arrival_time_base_planned' => fn($v) =>
        $v['arrival_time_base_planned'] ?? null,

    /**
     * =========================================
     * 🧭 BASE - ACTUAL
     * =========================================
     */

    'departure_date_base_actual' => fn($v) =>
        $this->toDate($v['departure_date_base_actual'] ?? null),

    'departure_time_base_actual' => fn($v) =>
        $v['departure_time_base_actual'] ?? null,

    'arrival_date_base_actual' => fn($v) =>
        $this->toDate($v['arrival_date_base_actual'] ?? null),

    'arrival_time_base_actual' => fn($v) =>
        $v['arrival_time_base_actual'] ?? null,

    /**
     * =========================================
     * 🏗 SITE - PLANNED
     * =========================================
     */

    'departure_date_site_planned' => fn($v) =>
        $this->toDate($v['departure_date_site_planned'] ?? null),

    'departure_time_site_planned' => fn($v) =>
        $v['departure_time_site_planned'] ?? null,

    'arrival_date_site_planned' => fn($v) =>
        $this->toDate($v['arrival_date_site_planned'] ?? null),

    'arrival_time_site_planned' => fn($v) =>
        $v['arrival_time_site_planned'] ?? null,

    /**
     * =========================================
     * 🏗 SITE - ACTUAL
     * =========================================
     */

    'departure_date_site_actual' => fn($v) =>
        $this->toDate($v['departure_date_site_actual'] ?? null),

    'departure_time_site_actual' => fn($v) =>
        $v['departure_time_site_actual'] ?? null,

    'arrival_date_site_actual' => fn($v) =>
        $this->toDate($v['arrival_date_site_actual'] ?? null),

    'arrival_time_site_actual' => fn($v) =>
        $v['arrival_time_site_actual'] ?? null,

    
],

        'relations' => [

        'mission_expenses' => function ($v) {
            // throw new \Exception(json_encode("ouiiii"));
            if (!isset($v['expenses'])) return [];



            $expenses = is_string($v['expenses'])
                ? json_decode($v['expenses'], true)
                : $v['expenses'];

            return collect($expenses)->map(function ($exp) {

                    $amount = $exp['amount'] ?? 0;

                    // $plannedQty = $exp['planned_quantity'] ?? 1;
                    // $actualQty  = $exp['actual_quantity'] ?? null;
                    $Qty  = $exp['actual_quantity'] ?? null;

                    return [
                        'expense_category_id' => $exp['expense_category_id'] ?? null,
                        'amount' => $amount,
                        'type' => $exp['type'] ?? 'PREVISIONNELLE',

                        // 'planned_quantity' => $plannedQty,
                        // 'actual_quantity' => $actualQty,
                        'quantity' => $Qty,

                        // ✅ TOTAL ajouté
                        // 'total' => $amount * ($Qty),

                        'comment' => $exp['comment'] ?? null,
                    ];
                })->toArray();
        }

    ]

      ]
            // Ajoute ici d'autres types si nécessaire
        ];

      if (!$type || !isset($map[$type])) {
    return [];
}

/**
 * 🔥 Data + relations config
 */
$dataLooper = $map[$type]['data'] ?? $map[$type];
$relationLooper = $map[$type]['relations'] ?? [];

$data = [];
$relationsData = [];

        // throw new \Exception(json_encode($relationLooper));


/**
 * =========================
 * 📦 MAIN DATA
 * =========================
 */
foreach ($dataLooper as $field => $callback) {

    if (!is_callable($callback)) {
        throw new \Exception("Callback invalide pour le champ: {$field}");
    }

    $data[$field] = $callback($validated);
}

/**
 * =========================
 * RELATIONS (IMPORTANT FIX)
 * =========================
 */
foreach ($relationLooper as $relation => $callback) {

    if (!is_callable($callback)) {
        throw new \Exception("Relation callback invalide pour: {$relation}");
    }

    $relationsData[$relation] = $callback($validated);
}

/**
 * =========================
 * COMMON
 * =========================
 */
$common = [
    'document_id' => $parentModel? $parentModel->id : null,
];

/**
 * =========================
 * RETURN FINAL STRUCTURE
 * =========================
 */
return [
    "data" => array_merge($data, $common),
    "relations" => $relationsData
];
   
    }


    private function toDate($value)
{
    if (empty($value)) {
        return null;
    }

    try {
        // format frontend: d-m-Y (ex: 02-05-2026)
        return \Carbon\Carbon::createFromFormat('d-m-Y', $value)
            ->format('Y-m-d');
    } catch (\Exception $e) {
        try {
            // fallback si déjà au format Y-m-d ou ISO
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}

private function resolveActorId($v)
{
    switch ($v['actor_type'] ?? null) {

        case 'me':
            return request()->get("user")['id'];
            // return auth()->id();
            

        case 'collaborator':
            return $v['actor_collaborator'] ?? null;

        case 'external':
            return $v['actor_external'] ?? null;

        default:
            return null;
    }
}

private function resolveActorType($v)
{
    switch ($v['actor_type'] ?? null) {

        case 'me':
        case 'collaborator':
            return 'INTERNAL';

        case 'external':
            return 'EXTERNAL';

        default:
            return null;
    }
}
}
