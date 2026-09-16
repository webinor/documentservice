<?php

namespace App\Services\Absence;

use App\DTO\LeaveCalculationRequest;
use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\UserServiceClient;
use Exception;
use Illuminate\Support\Facades\Log;

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

        Log::info('AbsenceDocumentEnrichmentHandler: début enrichissement', [
            'document_id' => $document->id,
            'document_type_id' => $document->document_type_id,
            'actor_type' => $document->actor_type,
            'actor_id' => $document->actor_id,
        ]);

        /*
         * =========================================================
         * ACTEUR
         * =========================================================
         */
        Log::info('AbsenceDocumentEnrichmentHandler: résolution de l’acteur', [
            'document_id' => $document->id,
            'actor_type' => $document->actor_type,
            'actor_id' => $document->actor_id,
        ]);

        $actorDetails = $this->userClient->resolveActor(
            $document->actor_type,
            $document->actor_id
        );

        Log::info('AbsenceDocumentEnrichmentHandler: acteur résolu', [
            'document_id' => $document->id,
            'actor_id' => $document->actor_id,
            'actor_name' => $actorDetails['name'] ?? null,
            'employee_id' => $actorDetails['employee_id'] ?? null,
        ]);

        $document->actor_details = $actorDetails;


        /*
         * =========================================================
         * DEMANDE D'ABSENCE
         * =========================================================
         */
        $absence = $document->absence_request;

        if (!$absence) {

            Log::warning(
                'AbsenceDocumentEnrichmentHandler: aucune demande d’absence trouvée',
                [
                    'document_id' => $document->id,
                ]
            );

            return $document->toArray();
        }

        Log::info(
            'AbsenceDocumentEnrichmentHandler: demande d’absence trouvée',
            [
                'document_id' => $document->id,
                'absence_id' => $absence->id,
                'type' => $absence->type,
                'leave_type_id' => $absence->leave_type_id,
                'departure_date' => $absence->departure_date,
                'return_date' => $absence->return_date,
            ]
        );

        /*
         * Chargement du type de congé.
         */
        $absence->load('leave_type');

        Log::info(
            'AbsenceDocumentEnrichmentHandler: type de congé chargé',
            [
                'document_id' => $document->id,
                'absence_id' => $absence->id,
                'leave_type_id' => $absence->leave_type_id,
                'leave_type_code' => $absence->leave_type->code ?? null,
                'leave_type_name' => $absence->leave_type->name ?? null,
            ]
        );


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
        

            Log::info(
                'AbsenceDocumentEnrichmentHandler: préparation de la simulation',
                [
                    'document_id' => $document->id,
                    'absence_id' => $absence->id,
                    'leave_type_id' => $absence->leave_type_id,
                    'start_date' => $absence->departure_date,
                    'end_date' => $absence->return_date,
                    'start_time' => $absence->departure_time,
                    'end_time' => $absence->return_time,
                    'employee_id' => $document->actor_id,
                ]
            );

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


            Log::info(
                'AbsenceDocumentEnrichmentHandler: LeaveCalculationRequest créé',
                [
                    'document_id' => $document->id,
                    'absence_id' => $absence->id,
                    'employee_id' => $document->actor_id,
                ]
            );


            /*
             * -----------------------------------------------------
             * Calcul complet avec le solde.
             * -----------------------------------------------------
             *
             * On réutilise exactement le même service que
             * LeaveSimulationController.
             */
            Log::info(
                'AbsenceDocumentEnrichmentHandler: appel calculateWithBalance',
                [
                    'document_id' => $document->id,
                    'absence_id' => $absence->id,
                    'employee_id' => $document->actor_id,
                ]
            );

            $simulation =
                $this->leaveCalculator->calculateWithBalance(
                    $calculationRequest,
                    request()->bearerToken()
                );

            Log::info(
                'AbsenceDocumentEnrichmentHandler: simulation calculée',
                [
                    'document_id' => $document->id,
                    'absence_id' => $absence->id,
                    'simulation' => $simulation,
                ]
            );

                
        } else {

            Log::info(
                'AbsenceDocumentEnrichmentHandler: simulation non exécutée',
                [
                    'document_id' => $document->id,
                    'absence_id' => $absence->id,
                    'type' => $absence->type,
                    'leave_type_id' => $absence->leave_type_id,
                    'departure_date' => $absence->departure_date,
                    'return_date' => $absence->return_date,
                ]
            );
        }


        /*
         * =========================================================
         * AJOUT DE LA SIMULATION
         * =========================================================
         */
        $absence->simulation = $simulation;

        Log::info(
            'AbsenceDocumentEnrichmentHandler: simulation ajoutée à la demande',
            [
                'document_id' => $document->id,
                'absence_id' => $absence->id,
                'simulation_present' => $simulation !== null,
            ]
        );


        /*
         * Remplacement de l'objet absence dans le document.
         */
        $document->absence_request = $absence;


        /*
         * =========================================================
         * RESULTAT
         * =========================================================
         */

        Log::info(
            'AbsenceDocumentEnrichmentHandler: enrichissement terminé',
            [
                'document_id' => $document->id,
                'absence_id' => $absence->id,
                'simulation_present' => $simulation !== null,
            ]
        );

        return $document->toArray();
    }
}