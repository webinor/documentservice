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
     */
    protected function definitions()
    {
        return [
            'title' => [
                'label' => 'Titre',
                'type' => 'text',
                'value' => function (array $document, array $workflowMetadata) {
                    return isset($document['title'])
                        ? $document['title']
                        : '';
                },
            ],

            'reference' => [
                'label' => 'Référence',
                'type' => 'text',
                'value' => function (array $document, array $workflowMetadata) {
                    return isset($document['reference'])
                        ? $document['reference']
                        : '';
                },
            ],

            'actor_full_name' => [
                'label' => 'Bénéficiaire',
                'type' => 'text',
                'value' => function (array $document, array $workflowMetadata) {
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

            'dynamic_amount' => [
                'label' => 'Montant',
                'type' => 'amount',
                'value' => function (array $document, array $workflowMetadata) {
                    /*
                     * Le document-service retourne actuellement
                     * le dynamic_amount sous la clé "amount".
                     *
                     * On garde également dynamic_amount si le document
                     * est disponible sous cette forme.
                     */
                    if (array_key_exists('dynamic_amount', $document)) {
                        return $document['dynamic_amount'];
                    }

                    return isset($document['amount'])
                        ? $document['amount']
                        : null;
                },
            ],

            'amount' => [
                'label' => 'Montant',
                'type' => 'amount',
                'value' => function (array $document, array $workflowMetadata) {
                    return isset($document['amount'])
                        ? $document['amount']
                        : null;
                },
            ],

            'created_at' => [
                'label' => 'Date de création',
                'type' => 'date',
                'value' => function (array $document, array $workflowMetadata) {
                    return isset($document['created_at'])
                        ? $document['created_at']
                        : null;
                },
            ],

            'status' => [
                'label' => 'Statut',
                'type' => 'text',
                'value' => function (array $document, array $workflowMetadata) {
                    return isset($document['status'])
                        ? $document['status']
                        : '';
                },
            ],

            /*
             * Le workflow-status ne vient PAS du document-service.
             *
             * Il est fourni par workflow-service dans workflow_metadata.
             */
            'workflow_status' => [
                'label' => 'Statut workflow',
                'type' => 'text',
                'value' => function (array $document, array $workflowMetadata) {
                    $status = data_get(
                        $workflowMetadata,
                        'workflow_status'
                    );

                    if (is_array($status)) {
                        return isset($status['label'])
                            ? $status['label']
                            : '';
                    }

                    return is_string($status)
                        ? $status
                        : '';
                },
            ],

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
            if (!isset($definitions[$column])) {
                throw ValidationException::withMessages([
                    'columns' => [
                        'Colonne d\'export inconnue : ' . $column,
                    ],
                ]);
            }

            $definition = $definitions[$column];

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