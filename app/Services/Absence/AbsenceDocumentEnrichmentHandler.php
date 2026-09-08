<?php

namespace App\Services\Absence;

use App\DTO\LeaveCalculationRequest;
use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\UserServiceClient;

class AbsenceDocumentEnrichmentHandler
    implements DocumentEnrichmentHandlerInterface
{
    protected LeaveCalculatorService $leaveCalculator;

    protected UserServiceClient $userClient;

    public function __construct(
        LeaveCalculatorService $leaveCalculator,
        UserServiceClient $userClient
    ) {
        $this->leaveCalculator = $leaveCalculator;
        $this->userClient = $userClient;
    }

    public function enrich(
        Document $document,
        array $base
    ): array {

        /*
         * =========================================================
         * ACTEUR
         * =========================================================
         */
        $actorDetails = $this->userClient->resolveActor(
            $document->actor_type,
            $document->actor_id
        );

        $document->actor_details = $actorDetails;


        /*
         * =========================================================
         * DEMANDE D'ABSENCE
         * =========================================================
         */
        $absence = $document->absence_request;

        if (!$absence) {
            return $document->toArray();
        }

        /*
         * Chargement du type de congé.
         */
        $absence->load('leave_type');


        /*
         * =========================================================
         * SIMULATION
         * =========================================================
         *
         * Une permission n'utilise pas le calculateur de congés.
         *
         * Pour un congé, on reconstruit exactement le même
         * LeaveCalculationRequest que celui utilisé par
         * LeaveSimulationController.
         */
        $simulation = null;

        if (
            $absence->type !== 'PERMISSION'
            && $absence->leave_type_id
            && $absence->departure_date
            && $absence->return_date
        ) {

        // throw new \Exception(json_encode([

        //             'leave_type_id' =>
        //                 $absence->leave_type_id,

        //             'start_date' =>
        //                 $absence->departure_date,

        //             'end_date' =>
        //                 $absence->return_date,

        //             'start_time' =>
        //                 $absence->departure_time,

        //             'end_time' =>
        //                 $absence->return_time,

        //             'employee_id' =>
        //                 $document->actor_id,
        //         ]), 1);
        

            $calculationRequest =
                new LeaveCalculationRequest([

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


            /*
             * -----------------------------------------------------
             * Calcul complet avec le solde.
             * -----------------------------------------------------
             *
             * On réutilise exactement le même service que
             * LeaveSimulationController.
             */
            $simulation =
                $this->leaveCalculator->calculateWithBalance(
                    $calculationRequest,
                    request()->bearerToken()
                );
        }


        /*
         * =========================================================
         * AJOUT DE LA SIMULATION
         * =========================================================
         */
        $absence->simulation = $simulation;


        /*
         * Remplacement de l'objet absence dans le document.
         */
        $document->absence_request = $absence;


        /*
         * =========================================================
         * RESULTAT
         * =========================================================
         */
        return $document->toArray();
    }
}
