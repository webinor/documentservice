<?php

namespace App\Services\Absence;

use App\Models\AbsenceRequest;
use App\Models\LeaveRequestDay;

class LeaveDayGeneratorService
{
    /**
     * Générer les jours d'une demande d'absence.
     *
     * La génération est idempotente :
     * les jours existants de la demande sont supprimés
     * avant de générer les nouveaux.
     */
    public function generate(
        AbsenceRequest $absence,
        array $simulation
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Supprimer les anciens jours
        |--------------------------------------------------------------------------
        |
        | Important lors d'une modification de la demande.
        |
        | Exemple :
        |
        | Avant :
        | 01/09
        | 02/09
        | 03/09
        |
        | Après modification :
        | 05/09
        | 06/09
        |
        | Les anciens jours doivent disparaître.
        |
        */

        LeaveRequestDay::query()
            ->where(
                'absence_request_id',
                $absence->id
            )
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | Vérifier la simulation
        |--------------------------------------------------------------------------
        */

        $days = $simulation['days'] ?? [];

        if (!is_array($days) || empty($days)) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Générer les nouveaux jours
        |--------------------------------------------------------------------------
        */

        $rows = [];

        $now = now();


        foreach ($days as $day) {

            if (
                empty($day['date'])
            ) {
                continue;
            }


            $rows[] = [

                'absence_request_id' =>
                    $absence->id,

                'date' =>
                    $day['date'],

                'leave_type_id' =>
                    $absence->leave_type_id,

                'coverage_type' =>
                    $day['coverage_type'] ?? null,

                'deducts_balance' =>
                    (bool) (
                        $day['deducts_balance']
                        ?? false
                    ),

                'deduct_days' =>
                    (float) (
                        $day['deduct_days']
                        ?? 0
                    ),

                'is_non_working_day' =>
                    !(
                        $day['is_working_day']
                        ?? false
                    ),

                'comment' =>
                    $day['comment'] ?? null,

                'created_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Insertion en une seule opération
        |--------------------------------------------------------------------------
        |
        | Plus performant que plusieurs LeaveRequestDay::create().
        |
        */

        if (!empty($rows)) {

            LeaveRequestDay::insert(
                $rows
            );
        }
    }
}