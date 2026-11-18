<?php

namespace App\Services\Mission;

use App\Models\Misc\Document;
use App\Models\Mission;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use App\Services\ReferenceGeneratorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class MissionService implements DocumentTypeHandlerInterface
{
    /**
     * @var ReferenceGeneratorService
     */
    private $referenceGenerator;

    public function __construct(
        ReferenceGeneratorService $referenceGenerator
    ) {
        $this->referenceGenerator = $referenceGenerator;
    }

    /**
     * Créer une mission et ses dépenses prévisionnelles.
     */
    public function create(
        Document $document,
        array $payload
    ): void {
        DB::transaction(function () use ($document, $payload) {

            // throw new Exception(json_encode($payload, 1));
            // throw new Exception(json_encode($this->getMissionAttributes($document, $payload, true)), 1);


            $mission = Mission::create(
                $this->getMissionAttributes($document, $payload, true)
            );

            $expenses = $this->normalizeExpenses(
                $payload['expenses'] ?? []
            );

            // throw new Exception(json_encode($expenses), 1);
            

            $this->createExpenses($mission, $expenses);
        });
    }

    /**
     * Mettre à jour une mission et ses dépenses.
     */
    public function update(
        Document $document,
        array $payload
    ): void {
        DB::transaction(function () use ($document, $payload) {

            $mission = Mission::where(
                'document_id',
                $document->id
            )->first();

            // Si la mission n'existe pas encore, on la crée.
            if (!$mission) {
                $this->create($document, $payload);
                return;
            }

            $mission->update(
                $this->getMissionAttributes($document, $payload)
            );

            // Ne toucher aux dépenses que si elles sont transmises.
            if (array_key_exists('expenses', $payload)) {
                $expenses = $this->normalizeExpenses(
                    $payload['expenses']
                );

                $this->updateExpenses($mission, $expenses);
            }
        });
    }

    /**
     * Préparer les attributs de la mission.
     */
    private function getMissionAttributes(
        Document $document,
        array $payload,
        bool $isCreating = false
    ): array {
        $attributes = [
            'destination' => $payload['destination'] ?? null,

            'scope' => $payload['scope'] ?? null,

            'estimated_budget' => $payload['estimated_budget'] ?? 0,

            'advance_amount' => $payload['advance_amount'] ?? 0,

            'is_special' => $payload['mission_special'] ?? false,

            /*
             * Dates et heures - BASE
             */
            'departure_date_base_planned' => $this->toDate(
                $payload['departure_date_base_planned'] ?? null
            ),

            'departure_time_base_planned' =>
                $payload['departure_time_base_planned'] ?? null,

            'arrival_date_base_planned' => $this->toDate(
                $payload['arrival_date_base_planned'] ?? null
            ),

            'arrival_time_base_planned' =>
                $payload['arrival_time_base_planned'] ?? null,

            /*
             * Dates et heures - SITE
             */
            'departure_date_site_planned' => $this->toDate(
                $payload['departure_date_site_planned'] ?? null
            ),

            'departure_time_site_planned' =>
                $payload['departure_time_site_planned'] ?? null,

            'arrival_date_site_planned' => $this->toDate(
                $payload['arrival_date_site_planned'] ?? null
            ),

            'arrival_time_site_planned' =>
                $payload['arrival_time_site_planned'] ?? null,
        ];

        if ($isCreating) {
            $attributes['document_id'] = $document->id;

            $attributes['code'] = $this->referenceGenerator->generate(
                'MISSION'
            );
        }

        return $attributes;
    }

    /**
     * Normaliser les dépenses reçues.
     *
     * Le frontend peut transmettre un tableau ou une chaîne JSON.
     */
    private function normalizeExpenses($expenses): array
    {
        if (is_string($expenses)) {
            $expenses = json_decode($expenses, true);
        }

        return is_array($expenses) ? $expenses : [];
    }

    /**
     * Créer les dépenses de la mission.
     */
    private function createExpenses(
        Mission $mission,
        array $expenses
    ): void {
        foreach ($expenses as $expense) {

            if (!is_array($expense)) {
                continue;
            }

            $mission->mission_expenses()->create(
                $this->getExpenseAttributes($expense)
            );
        }
    }

    /**
     * Mettre à jour les dépenses existantes ou créer les nouvelles.
     *
     * Les identifiants locaux UUID du frontend ne sont pas
     * considérés comme des identifiants de dépenses en base.
     */
    private function updateExpenses(
        Mission $mission,
        array $expenses
    ): void {
        foreach ($expenses as $expense) {

            if (!is_array($expense)) {
                continue;
            }

            $attributes = $this->getExpenseAttributes($expense);

            $expenseId = $expense['id'] ?? null;

            if (
                $expenseId !== null &&
                is_numeric($expenseId)
            ) {
                $existingExpense = $mission
                    ->mission_expenses()
                    ->where('id', $expenseId)
                    ->first();

                if ($existingExpense) {
                    $existingExpense->update($attributes);
                    continue;
                }
            }

            $mission->mission_expenses()->create($attributes);
        }
    }

    /**
     * Préparer les attributs d'une dépense.
     */
    private function getExpenseAttributes(array $expense): array
    {
        return [
            'expense_category_id' =>
                $expense['expense_category_id'] ?? null,

            'amount' =>
                $expense['amount'] ?? 0,

            'quantity' =>
                $expense['quantity']
                ?? $expense['planned_quantity']
                ?? $expense['actual_quantity']
                ?? 1,

            'type' =>
                $expense['type'] ?? 'PREVISIONNELLE',

            'comment' =>
                $expense['comment'] ?? null,
        ];
    }

    /**
     * Convertir une date en format Y-m-d.
     *
     * Formats acceptés :
     * - d-m-Y
     * - Y-m-d
     * - ISO
     */
    private function toDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return Carbon::createFromFormat(
                    'Y-m-d',
                    $value
                )->format('Y-m-d');
            }

            return Carbon::createFromFormat(
                'd-m-Y',
                $value
            )->format('Y-m-d');

        } catch (\Exception $e) {

            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    /**
     * Résoudre l'identifiant de l'acteur.
     */
    private function resolveActorId(array $data)
    {
        switch ($data['actor_type'] ?? null) {

            case 'me':
                return request()->input('user.id');

            case 'collaborator':
                return $data['actor_collaborator'] ?? null;

            case 'external':
                return $data['actor_external'] ?? null;

            default:
                return null;
        }
    }

    /**
     * Résoudre le type de l'acteur.
     */
    private function resolveActorType(array $data): ?string
    {
        switch ($data['actor_type'] ?? null) {

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