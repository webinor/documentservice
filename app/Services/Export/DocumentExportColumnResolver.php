<?php

namespace App\Services\Export;

use Illuminate\Validation\ValidationException;

class DocumentExportColumnResolver
{
    /**
     * Colonnes disponibles pour les exports.
     *
     * IMPORTANT :
     * On n'accepte jamais de chemin arbitraire envoyé
     * par le frontend.
     *
     * Chaque colonne possède :
     * - label : libellé affiché dans Excel
     * - type  : type de donnée utilisé pour le formatage
     * - value : fonction permettant de récupérer la valeur
     */
    protected function definitions()
    {
        return [
            /*
             * ------------------------------------------------------------------
             * Titre du document
             * ------------------------------------------------------------------
             */
            'title' => [
                'label' => 'Titre',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    return isset($document['title'])
                        ? $document['title']
                        : '';
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Référence du document
             * ------------------------------------------------------------------
             */
            'reference' => [
                'label' => 'Référence',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    return isset($document['reference'])
                        ? $document['reference']
                        : '';
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Créateur du document
             * ------------------------------------------------------------------
             *
             * Le document-service fournit les informations du créateur
             * dans la clé creator_details.
             *
             * Exemple :
             * creator_details.nom
             * creator_details.prenom
             */
            'creator_full_name' => [
                'label' => 'Créé par',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    $nom = data_get(
                        $document,
                        'creator_details.nom',
                        ''
                    );

                    $prenom = data_get(
                        $document,
                        'creator_details.prenom',
                        ''
                    );

                    return trim($nom . ' ' . $prenom);
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Bénéficiaire
             * ------------------------------------------------------------------
             *
             * Le bénéficiaire est récupéré depuis actor_details.
             */
            'actor_full_name' => [
                'label' => 'Bénéficiaire',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    $nom = data_get(
                        $document,
                        'actor_details.nom',
                        ''
                    );

                    $prenom = data_get(
                        $document,
                        'actor_details.prenom',
                        ''
                    );

                    return trim($nom . ' ' . $prenom);
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Type de régularisation
             * ------------------------------------------------------------------
             *
             * Cette information est spécifique aux fiches à régulariser.
             *
             * Exemple :
             * regularization_sheet.regularization_type
             *
             * Si la donnée n'existe pas pour un autre type de document,
             * une chaîne vide est retournée.
             */
           
            'regularization_sheet.regularization_type' => [
                'label' => 'Type de régularisation',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    $type = data_get(
                        $document,
                        'regularization_sheet.regularization_type',
                        ''
                    );

                    return $type === 'INTERNAL'
                        ? 'FONCTIONNEMENT INTERNE'
                        : ($type === 'ASSISTANCE'
                            ? 'ASSISTANCE'
                            : $type);
                },
            ],



            /*
             * ------------------------------------------------------------------
             * Montant dynamique
             * ------------------------------------------------------------------
             *
             * Le document-service retourne actuellement le dynamic_amount
             * sous la clé "amount" dans certains cas.
             *
             * On garde également dynamic_amount si le document
             * est disponible sous cette forme.
             */
            'dynamic_amount' => [
                'label' => 'Montant',
                'type' => 'amount',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    if (array_key_exists(
                        'dynamic_amount',
                        $document
                    )) {
                        return $document['dynamic_amount'];
                    }

                    return isset($document['amount'])
                        ? $document['amount']
                        : null;
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Montant réel
             * ------------------------------------------------------------------
             *
             * Utilisé notamment pour les fiches à régulariser.
             */
            'actual_amount' => [
                'label' => 'Montant réel',
                'type' => 'amount',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    return array_key_exists(
                        'actual_amount',
                        $document
                    )
                        ? $document['actual_amount']
                        : null;
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Montant classique
             * ------------------------------------------------------------------
             *
             * Conservé pour les documents qui utilisent directement
             * la propriété amount.
             */
            'amount' => [
                'label' => 'Montant',
                'type' => 'amount',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    return isset($document['amount'])
                        ? $document['amount']
                        : null;
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Date de création
             * ------------------------------------------------------------------
             */
            'created_at' => [
                'label' => 'Date de création',
                'type' => 'date',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    return isset($document['created_at'])
                        ? $document['created_at']
                        : null;
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Statut du document
             * ------------------------------------------------------------------
             *
             * Cette valeur correspond au statut directement fourni
             * par le document-service.
             */
            'status' => [
                'label' => 'Statut',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    return isset($document['status'])
                        ? $document['status']
                        : '';
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Statut du workflow
             * ------------------------------------------------------------------
             *
             * Le workflow_status ne vient PAS du document-service.
             *
             * Il est fourni par workflow-service dans workflow_metadata.
             */
            'workflow_status' => [
                'label' => 'Statut workflow',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    $status = data_get(
                        $workflowMetadata,
                        'workflow_status'
                    );

                    /*
                     * Lorsque workflow_status est un objet/tableau,
                     * on récupère son libellé.
                     */
                    if (is_array($status)) {
                        return isset($status['label'])
                            ? $status['label']
                            : '';
                    }

                    /*
                     * Supporte également le cas où le workflow-service
                     * fournit directement le statut sous forme de chaîne.
                     */
                    return is_string($status)
                        ? $status
                        : '';
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Initiateur de l'avance
             * ------------------------------------------------------------------
             *
             * Cette information est fournie directement par le
             * document-service dans advance_initiator_details.
             *
             * IMPORTANT :
             * On ne retourne jamais l'objet complet afin d'éviter
             * d'exporter accidentellement des informations sensibles
             * comme le password.
             */
            'advance_initiator_details' => [
                'label' => 'Initiateur de l\'avance',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    $name = data_get(
                        $document,
                        'advance_initiator_details.name',
                        ''
                    );

                    $prenom = data_get(
                        $document,
                        'advance_initiator_details.prenom',
                        ''
                    );

                    return trim($name . ' ' . $prenom);
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Initiateur de la régularisation
             * ------------------------------------------------------------------
             *
             * Même principe que pour l'initiateur de l'avance.
             *
             * On extrait uniquement le nom et le prénom.
             */
            'regularization_initiator_details' => [
                'label' => 'Initiateur de la régularisation',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    $name = data_get(
                        $document,
                        'regularization_initiator_details.name',
                        ''
                    );

                    $prenom = data_get(
                        $document,
                        'regularization_initiator_details.prenom',
                        ''
                    );

                    return trim($name . ' ' . $prenom);
                },
            ],

            /*
             * ------------------------------------------------------------------
             * Initiateur de transaction
             * ------------------------------------------------------------------
             *
             * Colonne conservée pour les autres types de documents
             * qui utilisent transaction_initiator_details.
             */
            'transaction_initiator_full_name' => [
                'label' => 'Initiateur de la transaction',
                'type' => 'text',
                'value' => function (
                    array $document,
                    array $workflowMetadata
                ) {
                    $name = data_get(
                        $document,
                        'transaction_initiator_details.name',
                        ''
                    );

                    $prenom = data_get(
                        $document,
                        'transaction_initiator_details.prenom',
                        ''
                    );

                    return trim($name . ' ' . $prenom);
                },
            ],
        ];
    }

    /**
     * Résout les colonnes demandées.
     *
     * @param array $requestedColumns
     * @return array
     */
    public function resolve(array $requestedColumns)
    {
        $definitions = $this->definitions();

        $columns = [];

        foreach (array_values(array_unique($requestedColumns)) as $column) {
            /*
             * On vérifie que la colonne demandée existe
             * dans la liste blanche définie ci-dessus.
             */
            if (!isset($definitions[$column])) {
                throw ValidationException::withMessages([
                    'columns' => [
                        'Colonne d\'export inconnue : ' . $column,
                    ],
                ]);
            }

            $definition = $definitions[$column];

            /*
             * On retourne uniquement les informations nécessaires
             * au générateur Excel.
             */
            $columns[] = [
                'key' => $column,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'value' => $definition['value'],
            ];
        }

        return $columns;
    }
}