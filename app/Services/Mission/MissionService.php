<?php

namespace App\Services\Document\Handlers;

use App\Models\Misc\Document;
use App\Models\Mission;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class MissionService implements DocumentTypeHandlerInterface
{
    public function create(
        Document $document,
        array $payload
    ): void {

        $mission = Mission::create([

            'document_id' => $document->id,

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
                $payload['departure_date_base_planned'],

            'departure_time_base_planned' =>
                $payload['departure_time_base_planned'],

            'arrival_date_base_planned' =>
                $payload['arrival_date_base_planned'],

            'arrival_time_base_planned' =>
                $payload['arrival_time_base_planned'],

            /**
             * SITE
             */

            'departure_date_site_planned' =>
                $payload['departure_date_site_planned'],

            'departure_time_site_planned' =>
                $payload['departure_time_site_planned'],

            'arrival_date_site_planned' =>
                $payload['arrival_date_site_planned'],

            'arrival_time_site_planned' =>
                $payload['arrival_time_site_planned'],
        ]);

        /**
         * Dépenses prévisionnelles
         */
        $this->createExpenses(
            $mission,
            $payload['expenses'] ?? []
        );
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