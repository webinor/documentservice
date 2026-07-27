<?php

namespace App\Services\Document;

use App\Services\UserServiceClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

class DocumentFilterService
{

    public function apply(
        Builder $query,
        array $filters
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

        

        


        // $this->filterStatus(
        //     $query,
        //     $filters
        // );


        // $this->filterAmount(
        //     $query,
        //     $filters
        // );


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


private function filterWorkflowDelay($query, array $filters)
{

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
        throw new \Exception(json_encode($response->body()), 1);
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
){

    if(
        empty($filters['amount_min'])
        &&
        empty($filters['amount_max'])
    ){
        return;
    }


    /*
       FUTUR :

       document_search_metadata.amount

    */


}



}