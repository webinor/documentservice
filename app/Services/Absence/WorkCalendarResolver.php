<?php

namespace App\Services\Absence;

use App\Models\PublicHoliday;
use App\Models\WorkCalendar;
use App\Models\WorkCalendarWorkingDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class WorkCalendarResolver
{
    /**
     * Résout le calendrier complet d'une période.
     *
     * Responsabilités :
     *
     * - jours travaillés
     * - jours non travaillés
     * - samedi sur deux
     * - jours fériés
     *
     * Ne gère PAS :
     *
     * - paid_days
     * - uses_balance
     * - deduct_excess_days
     * - règles propres au type de congé
     */
    public function resolvePeriod(
        WorkCalendar $calendar,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {

        $startDate = $startDate->copy()->startOfDay();
        $endDate   = $endDate->copy()->startOfDay();

        if ($endDate->lt($startDate)) {
            throw new \InvalidArgumentException(
                'La date de fin doit être supérieure ou égale à la date de début.'
            );
        }

        /*
         * ---------------------------------------------------------
         * Jours habituels du calendrier
         * ---------------------------------------------------------
         *
         * Exemple :
         *
         * 1 = lundi
         * 2 = mardi
         * ...
         * 6 = samedi
         * 7 = dimanche
         */
        $workingDays = WorkCalendarWorkingDay::query()
            ->where(
                'work_calendar_id',
                $calendar->id
            )
            ->get()
            ->keyBy('day_of_week');


        /*
         * ---------------------------------------------------------
         * Jours fériés de la période
         * ---------------------------------------------------------
         *
         * On charge uniquement les jours concernés.
         */
        $publicHolidays = PublicHoliday::query()
            ->where(
                'work_calendar_id',
                $calendar->id
            )
            ->whereBetween(
                'date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]
            )
            ->get()
            ->keyBy(function ($holiday) {

                return Carbon::parse(
                    $holiday->date
                )->format('Y-m-d');

            });


        /*
         * ---------------------------------------------------------
         * Génération de la période
         * ---------------------------------------------------------
         */
        $period = CarbonPeriod::create(
            $startDate,
            $endDate
        );


        $days = collect();


        /*
         * ---------------------------------------------------------
         * Compteur des samedis
         * ---------------------------------------------------------
         *
         * IMPORTANT :
         *
         * Ce compteur est relatif à LA DEMANDE.
         *
         * samedi #1 = travaillé
         * samedi #2 = repos
         * samedi #3 = travaillé
         * samedi #4 = repos
         *
         */
        $saturdayNumber = 0;


        foreach ($period as $date) {

            $date = $date->copy()->startOfDay();

            $dateString =
                $date->format('Y-m-d');

            $dayOfWeek =
                $date->dayOfWeekIso;


            /*
             * -----------------------------------------------------
             * Configuration hebdomadaire
             * -----------------------------------------------------
             */
            $workingDay =
                $workingDays->get($dayOfWeek);


            $isWorkingDay = false;

            $countsForLeave = false;

            $workingRatio = 0;


            if ($workingDay) {

                $isWorkingDay =
                    (bool) $workingDay->is_working_day;

                $countsForLeave =
                    (bool) $workingDay->counts_for_leave;

                $workingRatio =
                    (float) $workingDay->working_ratio;
            }


            /*
             * -----------------------------------------------------
             * SAMEDI SUR DEUX
             * -----------------------------------------------------
             *
             * Le samedi est le jour ISO 6.
             */
            if ($dayOfWeek === 6) {

                $saturdayNumber++;


                /*
                 * 1er samedi = travaillé
                 * 2e samedi = repos
                 * 3e samedi = travaillé
                 * 4e samedi = repos
                 */
                $isAlternateSaturdayWorking =
                    ($saturdayNumber % 2) === 1;


                if ($isAlternateSaturdayWorking) {

                    $isWorkingDay = true;

                    $countsForLeave = true;

                    $workingRatio = 1;

                } else {

                    $isWorkingDay = false;

                    $countsForLeave = false;

                    $workingRatio = 0;
                }
            }


            /*
             * -----------------------------------------------------
             * JOUR FÉRIÉ
             * -----------------------------------------------------
             */
            $publicHoliday =
                $publicHolidays->get(
                    $dateString
                );


            $isPublicHoliday =
                $publicHoliday !== null;


            $comment = null;


            if ($publicHoliday) {

                $comment =
                    $publicHoliday->name;


                /*
                 * Le jour férié peut être :
                 *
                 * counts_for_leave = false
                 *
                 * => ne compte pas comme jour de congé.
                 */
                if (!$publicHoliday->counts_for_leave) {

                    $countsForLeave = false;
                }
            }


            /*
             * -----------------------------------------------------
             * Résultat du jour
             * -----------------------------------------------------
             */
            $days->push([

                'date' =>
                    $dateString,

                'day_name' =>
                    $date
                        ->locale('fr')
                        ->dayName,

                'day_of_week' =>
                    $dayOfWeek,

                'is_working_day' =>
                    $isWorkingDay,

                'is_public_holiday' =>
                    $isPublicHoliday,

                'counts_for_leave' =>
                    $countsForLeave,

                'working_ratio' =>
                    $workingRatio,

                'comment' =>
                    $comment,

            ]);
        }


        return $days;
    }
}