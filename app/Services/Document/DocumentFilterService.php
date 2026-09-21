<?php

namespace App\Services\Document;

use App\Services\UserServiceClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

class DocumentFilterService
{

    public function apply(
        Builder $query,
        array $filters,
        array $documentTypes = []
    ): Builder {

        // $this->filterReference(
        //     $query,
        //     $filters
        // );


        // $this->filterDate(
        //     $query,
        //     $filters
        // );


        // $this->filterEmployee(
        //     $query,
        //     $filters
        // );

        $this->filterCity(
            $query,
            $filters
        );


        $this->filterDepartment(
            $query,
            $filters
        );

        $this->filterCreatedSince(
            $query,
            $filters
        );

        $this->filterWorkflowDelay(
            $query,
            $filters
        );

        /*
        |--------------------------------------------------------------------------
        | Filtre par trésorier
        |--------------------------------------------------------------------------
        |
        | Le trésorier est identifié à partir de la transaction financière
        | ayant été initiée par lui.
        |
        | financial_transactions.created_by = employee/user ayant
        | initié la transaction.
        |
        */
        $this->filterTreasurer(
            $query,
            $filters,
            $documentTypes
        );
        

        


        // $this->filterStatus(
        //     $query,
        //     $filters
        // );


        $this->filterAmount(
            $query,
            $filters
        );


        return $query;
    }


    private function filterReference(
        Builder $query,
        array $filters
    ){

        if(empty($filters['reference'])){
            return;
        }


        $query->where(
            'reference',
            'like',
            "%{$filters['reference']}%"
        );

    }


    private function filterTreasurer(
        Builder $query,
        array $filters,
        array $documentTypes = []
    ){

        if(empty($filters['treasurer_id'])){
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Aucun type de document disponible
        |--------------------------------------------------------------------------
        |
        | Le filtre ne peut pas déterminer quelle relation métier utiliser
        | pour accéder aux transactions financières.
        |
        */
        if(empty($documentTypes)){
            return;
        }

        $treasurerId = $filters['treasurer_id'];

        /*
        |--------------------------------------------------------------------------
        | Recherche du document par rapport à son type
        |--------------------------------------------------------------------------
        |
        | Exemple :
        |
        | Document
        |    ↓
        | regularizationSheet
        |    ↓
        | financialTransactions
        |    ↓
        | created_by = treasurer_id
        |
        | ou :
        |
        | Document
        |    ↓
        | taxiPaper
        |    ↓
        | financialTransactions
        |    ↓
        | created_by = treasurer_id
        |
        */

        $query->where(function ($q) use (
            $documentTypes,
            $treasurerId
        ) {

            foreach ($documentTypes as $relation) {

                /*
                |--------------------------------------------------------------------------
                | Un document peut correspondre à l'un des types sélectionnés.
                |--------------------------------------------------------------------------
                |
                | On utilise donc OR entre les différentes relations.
                |
                */
                $q->orWhereHas(
                    $relation,
                    function ($documentQuery) use (
                        $treasurerId
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | Recherche d'une transaction financière
                        |--------------------------------------------------------------------------
                        |
                        | financial_transactions.transactable_id
                        | et
                        | financial_transactions.transactable_type
                        |
                        | sont gérés automatiquement par la relation
                        | morphMany / morphOne.
                        |
                        */
                        $documentQuery->whereHas(
                            'financialTransactions',
                            function ($transactionQuery) use (
                                $treasurerId
                            ) {

                                /*
                                |--------------------------------------------------------------------------
                                | Le trésorier est celui qui a créé/inité
                                | la transaction financière.
                                |--------------------------------------------------------------------------
                                */
                                $transactionQuery->where(
                                    'created_by',
                                    $treasurerId
                                );
                            }
                        );
                    }
                );
            }
        });

    }


    private function filterDate(
        Builder $query,
        array $filters
    ){

        if(!empty($filters['date_start'])){

            $query->whereDate(
                'created_at',
                '>=',
                $filters['date_start']
            );

        }


        if(!empty($filters['date_end'])){

            $query->whereDate(
                'created_at',
                '<=',
                $filters['date_end']
            );

        }

    }


    private function filterEmployee(
        Builder $query,
        array $filters
    ){

        if(empty($filters['employee_id'])){
            return;
        }


        $query->where(
            'actor_id',
            $filters['employee_id']
        );

    }

        private function filterCity(
        Builder $query,
        array $filters
    ){

        if(empty($filters['city'])){
            return;
        }


        /*
            Aujourd'hui :
            on passe par actor_id

            Demain :
            document_search_metadata.department_id
        */


        $employees =
            app(UserServiceClient::class)
            ->employeesByCity(
                $filters['city']
            );

        // throw new \Exception(
        //         json_encode($filters),
        //         1
        //     );

        $employeeIds = sizeof($employees) > 0 ? collect($employees)->pluck('id') : [];


        $query->whereIn(
            'actor_id',
            $employeeIds
        );

    }


    private function filterDepartment(
        Builder $query,
        array $filters
    ){

        if(empty($filters['department_id'])){
            return;
        }


        /*
            Aujourd'hui :
            on passe par actor_id

            Demain :
            document_search_metadata.department_id
        */


        $employeeIds =
            app(UserServiceClient::class)
            ->employeesByDepartment(
                $filters['department_id']
            );


        $query->whereIn(
            'actor_id',
            $employeeIds
        );

    }


    private function filterWorkflowDelay(
        $query,
        array $filters
    ){

        if (empty($filters['waiting_time'])) {
            return;
        }


        $response = Http::withToken(
            request()->bearerToken()
        )
        ->acceptJson()
        ->get(
            config('services.workflow_service.base_url')
            . '/workflow-delay-documents',
            [
                'delay' => $filters['waiting_time']
            ]
        );





        if ($response->failed()) {
            throw new \Exception(
                json_encode($response->body()),
                1
            );

            return;
        }


        $documentIds = $response->json('data');


        $query->whereIn(
            'id',
            $documentIds
        );
    }


    private function filterCreatedSince(
        Builder $query,
        array $filters
    ){


        if (!empty($filters['created_since'])) {

            switch ($filters['created_since']) {

                case 'today':

                    $query->whereDate(
                        'created_at',
                        now()->toDateString()
                    );

                    break;


                case 'this_week':

                    $query->whereBetween(
                        'created_at',
                        [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ]
                    );

                    break;


                case '7_days':

                    $query->where(
                        'created_at',
                        '<=',
                        now()->subDays(7)
                    );

                    break;


                case '30_days':

                    $query->where(
                        'created_at',
                        '<=',
                        now()->subDays(30)
                    );

                    break;


                case '90_days':

                    $query->where(
                        'created_at',
                        '<=',
                        now()->subDays(90)
                    );

                    break;
            }
        }


    }


    private function filterStatus(
        Builder $query,
        array $filters
    ){

        if(empty($filters['statut'])
           || $filters['statut']=="ALL"
        ){
            return;
        }


        $query->where(
            'status',
            $filters['statut']
        );

    }


    private function filterAmount(
        Builder $query,
        array $filters
    ): void {

        $min = $filters['amount'][0] ?? null;
        $max = $filters['amount'][1] ?? null;

        // throw new \Exception($max, 1);
        

        if ($min === null && $max === null) {
            return;
        }

        if ($min !== null && $max !== null) {
            $query->whereBetween(
                'amount',
                [$min, $max]
            );

            return;
        }

        if ($min !== null) {
            $query->where(
                'amount',
                '>=',
                $min
            );
        }

        if ($max !== null) {
            $query->where(
                'amount',
                '<=',
                $max
            );
        }
    }



}