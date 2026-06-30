<?php

namespace App\Services\Templates;

use App\Models\Mission;
use App\Services\Mission\MissionExpenseService;

class MissionTemplateDataBuilder
{
    public function build($document , array $head_of_department_data)
    {

    $mission =$document['mission'];
    $transactions =$document['transactions'];

    $advanceTransaction = collect($transactions)
    ->first(function ($transaction) {
        return $transaction['transaction_type_code'] === 'MISSION_EXPENSE_ADVANCE'
            && $transaction['status'] === 'COMPLETED';
    });

    // $functions = collect($document['roles'] ?? [])
    // ->pluck('name')
    // ->implode(', ');

    $function = $document['actor_details']['organization']['position']['position']['name'] ?? null;

    // $manager = $document['actor_details']['manager']['name'] ?? '';

    // $managerFunction =$document['actor_details']['manager']['department_data']['position']['position']['name'] ?? null;

    // throw new \Exception(json_encode($head_of_department_data), 1);
    // throw new \Exception(json_encode($document['actor_details']['department_data']), 1);
    

    $expenseService = app(MissionExpenseService::class);

    $mission = Mission::find($mission['id']);
    
    $missionExpenses = collect($expenseService->calculate($mission)['expenses']);
    $data = [
    'mission.code' => "FICHE DE MISSION #{$mission['code']}",
    'mission.title' => $mission['document']['title'] ?? '',
    'mission.destination' => $mission['destination'] ?? '',

    'mission.departure_date' => !empty($mission['departure_date_base_planned'])
        ? date('d/m/Y', strtotime($mission['departure_date_base_planned']))
        : '',

        

    'mission.arrival_date' => !empty($mission['arrival_date_base_planned'])
        ? date('d/m/Y', strtotime($mission['arrival_date_base_planned']))
        : '',

    'mission.departure_base_date' => $this->formatDateTime(
        $mission['departure_date_base_planned'] ?? null,
        $mission['departure_time_base_planned'] ?? null
    ),

    'mission.arrival_site_date' => $this->formatDateTime(
        $mission['arrival_date_site_planned'] ?? null,
        $mission['arrival_time_site_planned'] ?? null
    ),

    'mission.departure_site_date' => $this->formatDateTime(
        $mission['departure_date_site_planned'] ?? null,
        $mission['departure_time_site_planned'] ?? null
    ),

    'mission.arrival_base_date' => $this->formatDateTime(
        $mission['arrival_date_base_planned'] ?? null,
        $mission['arrival_time_base_planned'] ?? null
    ),

    'agent.name' => $document['actor_details']['nom'] ?? '',
    'agent.position' => $function ?? '',

    'mission.contractor.name' => $head_of_department_data['name'] ?? '',
    'mission.contractor.position' => $head_of_department_data['department_data']['position']['position']['name'] ?? '',

    'advance.amount' => $advanceTransaction['amount'] ?? 0,
];



        $data['expenses.previsionnelles'] = $missionExpenses
    ->where('type', 'PREVISIONNELLE')
    ->map(function ($expense) {

    
        return [

            'label' => $expense->expense_category->name,

            'quantity' => $expense->planned_quantity,

            'amount' => $expense->amount,

            'total' => $expense->planned_total,

        ];
    })
    ->values()
    ->toArray();

    // throw new \Exception(json_encode($missionExpenses), 1);



    $data['expenses.declarees'] = $missionExpenses
    ->where('type', 'DECLAREE')
    ->map(function ($expense) {

        return [

            'label' => $expense->expense_category->name ?? "--",

            'quantity' => $expense->final_quantity,

            'amount' => $expense->amount,

            'total' => $expense->final_total,

        ];
    })
    ->values()
    ->toArray();



return $data;;






    }

    private function formatDateTime(
    ?string $date,
    ?string $time
): string {
    if (empty($date)) {
        return '';
    }

    $formattedDate = date(
        'd/m/Y',
        strtotime($date)
    );

    if (empty($time)) {
        return $formattedDate;
    }

    return $formattedDate . ' ' . substr($time, 0, 5);
}
}