<?php

namespace App\Services\Export;

use Exception;
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
 * Date de paiement de l'avance
 * ------------------------------------------------------------------
 *
 * Correspond à REGULARIZATION_ADVANCE.
 */
'advance_initiation_details' => [
    'label' => 'Avance payée le',
    'type' => 'date',
    'value' => function (
        array $document,
        array $workflowMetadata
    ) {
        return $this->extractTransactionDateByCode(
            $document,
            'REGULARIZATION_ADVANCE'
        );
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
                        'settlement_initiator_details.name',
                        ''
                    );

                    $prenom = data_get(
                        $document,
                        'settlement_initiator_details.prenom',
                        ''
                    );

                    return trim($name . ' ' . $prenom);
                },
            ],

                        /*
 * ------------------------------------------------------------------
 * Date de régularisation
 * ------------------------------------------------------------------
 *
 * Correspond à la transaction REGULARIZATION_SETTLEMENT.
 */
'regularization_initiation_details' => [
    'label' => 'Régularisation effectuée le',
    'type' => 'date',
    'value' => function (
        array $document,
        array $workflowMetadata
    ) {
         return $this->extractTransactionDateByCode(
            $document,
            'REGULARIZATION_SETTLEMENT'
        );
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





/*
 * ------------------------------------------------------------------
 * Date de paiement du papier taxi
 * ------------------------------------------------------------------
 *
 * Correspond à TAXI_PAPER_SETTLEMENT.
 */
'payment_details' => [
    'label' => 'Papier taxi payé le',
    'type' => 'date',
    'value' => function (
        array $document,
        array $workflowMetadata
    ) {
          return $this->extractTransactionDateByCode(
            $document,
            'TAXI_PAPER_SETTLEMENT'
        );
    },
],


/*
 * ------------------------------------------------------------------
 * Date de paiement de la note de frais
 * ------------------------------------------------------------------
 *
 * Correspond à FEE_NOTE_SETTLEMENT.
 */
'fee_note_payment_details' => [
    'label' => 'Note de frais payée le',
    'type' => 'date',
    'value' => function (
        array $document,
        array $workflowMetadata
    ) {
         return $this->extractTransactionDateByCode(
            $document,
            'FEE_NOTE_SETTLEMENT'
        );
    },
],

/*
 * ------------------------------------------------------------------
 * Date de clôture du workflow
 * ------------------------------------------------------------------
 */
'workflow_closed_at' => [
    'label' => 'Clôturé le',
    'type' => 'date',
    'value' => function (
        array $document,
        array $workflowMetadata
    ) {
        $value = data_get(
        $workflowMetadata,
        'workflow_availability.workflow_closed_at'
    );

    if ($document['uuid'] == "1160897e-a286-460d-a2ae-0627eea49252") {
        # code...
        // throw new Exception(json_encode($workflowMetadata['workflow_availability']), 1);
    }
    

    // logger()->debug('Export - workflow_closed_at', [
    //     'document' => $document,
    //     'document_id' => data_get($document, 'id'),
    //     'workflow_availability_exists' => isset($document['workflow_availability']),
    //     'value' => $value,
    // ]);

    return $value;
    },
],
        ];
    }

    /**
 * Extrait la date d'une transaction.
 *
 * Prend en charge :
 * - une date directement représentée par une chaîne ;
 * - un tableau contenant signed_at ;
 * - un tableau contenant paid_at ;
 * - un tableau contenant transaction.signed_at ;
 * - une collection de transactions.
 *
 * @param mixed $details
 * @return string|null
 */
protected function extractTransactionDate($details)
{
    if (empty($details)) {
        return null;
    }

    // Cas où la valeur est directement une date.
    if (is_string($details)) {
        return $details;
    }

    // Cas où le détail est un tableau de transactions.
    if (is_array($details)) {
        // Si plusieurs transactions sont présentes,
        // on privilégie la première transaction exploitable.
        $isList = count($details) > 0
    && array_keys($details) ===
        range(0, count($details) - 1);

if ($isList) {
            foreach ($details as $transaction) {
                $date = $this->extractTransactionDate(
                    $transaction
                );

                if (!empty($date)) {
                    return $date;
                }
            }

            return null;
        }

        // Essayer les différentes clés possibles.
        $datePaths = [
            'signed_at',
            'paid_at',
            'payment_date',
            'transaction_date',
            'created_at',
            'transaction.signed_at',
            'transaction.paid_at',
        ];

        foreach ($datePaths as $path) {
            $date = data_get($details, $path);

            if (!empty($date) && is_string($date)) {
                return $date;
            }
        }
    }

    return null;
}

/**
 * Récupère la date d'une transaction à partir de son code.
 *
 * Sources :
 * - document.transactions
 *
 * Prend en charge :
 * - une collection Laravel ;
 * - un tableau de transactions ;
 * - un objet contenant les transactions ;
 * - une transaction unique.
 *
 * @param array $document
 * @param string $transactionTypeCode
 * @return string|null
 */
protected function extractTransactionDateByCode(
    array $document,
    string $transactionTypeCode
) {
    $transactions = data_get(
        $document,
        'transactions',
        []
    );

    /*
     * Convertir une collection Laravel en tableau.
     */
    if ($transactions instanceof \Illuminate\Support\Collection) {
        $transactions = $transactions->all();
    }

    /*
     * Si transactions est un objet, tenter de récupérer
     * les éléments via ses propriétés publiques ou sa
     * méthode toArray().
     */
    elseif (is_object($transactions)) {
        if (method_exists($transactions, 'toArray')) {
            $transactions = $transactions->toArray();
        } else {
            $transactions = (array) $transactions;
        }
    }

    if (!is_array($transactions)) {
        return null;
    }

    /*
     * Certaines structures sérialisées contiennent les
     * transactions dans une propriété items.
     *
     * Après conversion d'un objet PHP en tableau, une
     * propriété protégée peut avoir une clé contenant
     * des caractères NUL.
     */
    $items = null;

    foreach ($transactions as $key => $value) {
        if (
            $key === 'items'
            || substr((string) $key, -5) === 'items'
        ) {
            $items = $value;
            break;
        }
    }

    if (is_array($items)) {
        $transactions = $items;
    }

    /*
     * Une transaction unique peut être représentée
     * directement par un tableau associatif.
     */
    if (isset($transactions['transaction_type_code'])) {
        $transactions = [$transactions];
    }

    /*
     * Rechercher exclusivement la transaction
     * correspondant au code demandé.
     */
    foreach ($transactions as $transaction) {
        /*
         * Normaliser chaque transaction en tableau.
         */
        if (is_object($transaction)) {
            if (method_exists($transaction, 'toArray')) {
                $transaction = $transaction->toArray();
            } else {
                $transaction = (array) $transaction;
            }
        }

        if (!is_array($transaction)) {
            continue;
        }

        if (
            !isset($transaction['transaction_type_code'])
            || $transaction['transaction_type_code']
                !== $transactionTypeCode
        ) {
            continue;
        }

        /*
         * signed_at est la date de signature/paiement
         * présente dans les données fournies.
         */
        if (!empty($transaction['signed_at'])) {
    return $this->formatExportDate(
        $transaction['signed_at']
    );
}

        return null;
    }

    return null;
}

/**
 * Formate une date pour l'export Excel.
 *
 * Le fuseau horaire source est UTC lorsque la date
 * se termine par Z. Le résultat est converti vers
 * Africa/Douala.
 *
 * @param mixed $date
 * @return string|null
 */
protected function formatExportDate($date)
{
    if (empty($date) || !is_string($date)) {
        return null;
    }

    try {
        return \Carbon\Carbon::parse($date)
            ->setTimezone('Africa/Douala')
            ->format('d-m-Y H:i');
    } catch (\Exception $e) {
        return null;
    }
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