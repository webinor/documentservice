<?php

namespace App\Services\Absence;

use App\DTO\LeaveCalculationRequest;
use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use Illuminate\Support\Facades\DB;

class AbsenceDocumentHandler implements DocumentTypeHandlerInterface
{
    protected AbsenceService $absenceService;
    protected LeaveCalculatorService $calculator;
    protected LeaveDayGeneratorService $generator;

    public function __construct(
        AbsenceService $absenceService,
        LeaveCalculatorService $calculator,
        LeaveDayGeneratorService $generator
    ) {
        $this->absenceService = $absenceService;
        $this->calculator = $calculator;
        $this->generator = $generator;
    }


    /**
     * Créer une demande d'absence.
     */
    public function create(
        Document $document,
        array $data
    ) {
        return DB::transaction(function () use (
            $document,
            $data
        ) {

            /*
            |--------------------------------------------------------------------------
            | Création de la demande
            |--------------------------------------------------------------------------
            */

            $absence = $this->absenceService->create(
                $document,
                $data
            );


            /*
            |--------------------------------------------------------------------------
            | Calcul des jours
            |--------------------------------------------------------------------------
            */

            $simulation = $this->calculate(
                $absence,
                $document
            );


            /*
            |--------------------------------------------------------------------------
            | Génération des jours d'absence
            |--------------------------------------------------------------------------
            */

            $this->generator->generate(
                $absence,
                $simulation
            );


            return $absence->fresh();
        });
    }


    /**
     * Modifier une demande d'absence.
     */
    public function update(
        Document $document,
        array $data
    ) {
        return DB::transaction(function () use (
            $document,
            $data
        ) {

            /*
            |--------------------------------------------------------------------------
            | Mise à jour de la demande
            |--------------------------------------------------------------------------
            */

            $absence = $this->absenceService->update(
                $document,
                $data
            );


            /*
            |--------------------------------------------------------------------------
            | Recalcul
            |--------------------------------------------------------------------------
            |
            | Les dates, horaires ou le type de congé peuvent avoir changé.
            | On recalcule donc systématiquement la simulation.
            |
            */

            $simulation = $this->calculate(
                $absence,
                $document
            );


            /*
            |--------------------------------------------------------------------------
            | Régénération des jours
            |--------------------------------------------------------------------------
            */

            $this->generator->generate(
                $absence,
                $simulation
            );


            return $absence->fresh();
        });
    }


    /**
     * Calculer les jours d'absence.
     */
    protected function calculate(
        $absence,
        Document $document
    ) {
        $request = new LeaveCalculationRequest([

            'leave_type_id' =>
                $absence->leave_type_id,

            'start_date' =>
                $absence->departure_date,

            'end_date' =>
                $absence->return_date,

            'start_time' =>
                $absence->departure_time,

            'end_time' =>
                $absence->return_time,

            'employee_id' =>
                $document->actor_id,
        ]);


        return $this->calculator->calculate(
            $request
        );
    }
}