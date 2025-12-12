<?php

namespace App\Services;

use Carbon\Carbon;

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

                  //  throw new \Exception(json_encode($validated));


        $className = $documentType->class_name;        // Exemple: \App\Models\InvoiceProvider
        $relationName = $documentType->relation_name;  // Exemple: invoiceProvider
        $type = $documentType->slug ?? null;           // Type utilisé pour mapper les données

        if (!class_exists($className)) {
            throw new \Exception("Classe {$className} introuvable !");
        }

        if (!method_exists($parentModel, $relationName)) {
            throw new \Exception("Relation '{$relationName}' introuvable sur " . get_class($parentModel));
        }

        // Mapper de données selon le type
        $data = $this->mapData($type, $validated , $parentModel);

        // Crée l'instance de l'enfant
        $child = new $className();

        $this->fillModelAttributes($child , $data);

        /*foreach ($data as $key => $value) {
            if (property_exists($child, $key)) {
                $child->$key = $value;
            }
            else{
                return $key;
            }
        }/**/

        //return $child;

        //throw new \Exception(json_encode($parentModel));


        // Sauvegarde via la relation
        $parentModel->$relationName()->save($child);
    }


    /**
 * Remplit dynamiquement un modèle avec des données en respectant $fillable.
 *
 * @param \Illuminate\Database\Eloquent\Model $model
 * @param array $data
 * @return void
 * @throws \Exception si une clé n'existe pas dans $fillable
 */
function fillModelAttributes($model, array $data)
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
                'title' => fn($v) => $v['title'] ?? null,
                'employee_id' => fn($v) => $v['employee_id'] ?? null,
                'start_date' => fn($v) => isset($v['start_date']) ? Carbon::parse($v['start_date'])->format('Y-m-d') : null,
                'end_date' => fn($v) => isset($v['end_date']) ? Carbon::parse($v['end_date'])->format('Y-m-d') : null,
                'description' => fn($v) => $v['description'] ?? null,
                'beneficiary' => fn($v) => $v['beneficiaire'] ?? null,
            ],
            // Ajoute ici d'autres types si nécessaire
        ];

        if (!$type || !isset($map[$type])) {
            return []; // Pas de mapping spécifique, on peut renvoyer un tableau vide
        }

        $data = [];
        foreach ($map[$type] as $field => $callback) {
            $data[$field] = $callback($validated);
        }

            // 2. Champs communs pour tous les types
    $common = [
        'document_id' => $parentModel? $parentModel->id : null,
        //'created_by'  => auth()->id(),
    ];

    return array_merge($data, $common);
    }
}
