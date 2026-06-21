<?php

namespace App\Services\Mission;


use App\Models\Misc\Document;
use App\Models\Mission;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use App\Services\ReferenceGeneratorService;

class MissionService implements DocumentTypeHandlerInterface
{

private ReferenceGeneratorService $referenceGenerator;

    public function __construct(ReferenceGeneratorService $referenceGenerator ) {
        $this->referenceGenerator = $referenceGenerator;
    }


    public function create(
        Document $document,
        array $payload
    ): void {



    

        $mission = Mission::create([

            'document_id' => $document->id,

            'reference' => $this->referenceGenerator->generate('MISSION'),

            'destination' => $payload['destination'] ?? null,

            'scope' => $payload['scope'] ?? null,

            'estimated_budget' =>
                $payload['estimated_budget'] ?? 0,

            'advance_amount' =>
                $payload['advance_amount'] ?? 0,

            'is_special' =>
                $payload['mission_special'] ?? false,

            'actor_id' =>
                $this->resolveActorId($payload),

            'actor_type' =>
                $this->resolveActorType($payload),

           /**
 * BASE
 */
'departure_date_base_planned' =>
    $this->toDate($payload['departure_date_base_planned'] ?? null),

'departure_time_base_planned' =>
    $payload['departure_time_base_planned'] ?? null,

'arrival_date_base_planned' =>
    $this->toDate($payload['arrival_date_base_planned'] ?? null),

'arrival_time_base_planned' =>
    $payload['arrival_time_base_planned'] ?? null,

/**
 * SITE
 */
'departure_date_site_planned' =>
    $this->toDate($payload['departure_date_site_planned'] ?? null),

'departure_time_site_planned' =>
    $payload['departure_time_site_planned'] ?? null,

'arrival_date_site_planned' =>
    $this->toDate($payload['arrival_date_site_planned'] ?? null),

'arrival_time_site_planned' =>
    $payload['arrival_time_site_planned'] ?? null,
        ]);

        /**
         * Dépenses prévisionnelles
         */
        $expenses = $payload['expenses'] ?? [];

        if (is_string($expenses)) {
            $expenses = json_decode($expenses, true) ?? [];
        }

        $this->createExpenses($mission, $expenses);
    }

        private function toDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // format frontend: d-m-Y (ex: 02-05-2026)
            return \Carbon\Carbon::createFromFormat("d-m-Y", $value)->format(
                "Y-m-d"
            );
        } catch (\Exception $e) {
            try {
                // fallback si déjà au format Y-m-d ou ISO
                return \Carbon\Carbon::parse($value)->format("Y-m-d");
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    private function createExpenses(
        Mission $mission,
        array $expenses
    ): void {

        foreach ($expenses as $expense) {

            $mission->mission_expenses()->create([

                'expense_category_id' =>
                    $expense['expense_category_id'],

                'amount' =>
                    $expense['amount'],

                'quantity' =>
                    $expense['quantity'] ?? 1,

                'type' =>
                    $expense['type'] ?? 'PREVISIONNELLE',

                'comment' =>
                    $expense['comment'] ?? null,
            ]);
        }
    }

    private function resolveActorId($v)
    {
        switch ($v["actor_type"] ?? null) {
            case "me":
                return request()->get("user")["id"];
            // return auth()->id();

            case "collaborator":
                return $v["actor_collaborator"] ?? null;

            case "external":
                return $v["actor_external"] ?? null;

            default:
                return null;
        }
    }

    private function resolveActorType($v)
    {
        switch ($v["actor_type"] ?? null) {
            case "me":
            case "collaborator":
                return "INTERNAL";

            case "external":
                return "EXTERNAL";

            default:
                return null;
        }
    }
}