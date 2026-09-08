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
         * Jours fériés
         * ---------------------------------------------------------
         *
         * On charge :
         *
         * 1. Les jours fériés explicites de la période.
         *
         * 2. TOUS les jours fériés récurrents du calendrier,
         *    même s'ils ont été enregistrés pour une autre année.
         *
         * Exemple :
         *
         * BDD :
         *
         * 2026-12-25
         * Noël
         * is_recurring = true
         *
         * Demande :
         *
         * 2027-12-20 -> 2027-12-31
         *
         * Le 25/12/2027 sera automatiquement reconnu
         * comme jour férié.
         */
        $publicHolidays = PublicHoliday::query()
            ->where(
                'work_calendar_id',
                $calendar->id
            )
            ->where(function ($query) use (
                $startDate,
                $endDate
            ) {

                /*
                 * Jours fériés explicites dans la période.
                 */
                $query->whereBetween(
                    'date',
                    [
                        $startDate->toDateString(),
                        $endDate->toDateString(),
                    ]
                )

                /*
                 * OU jours fériés récurrents,
                 * quelle que soit leur année.
                 */
                ->orWhere(
                    'is_recurring',
                    true
                );
            })
            ->get();


        /*
         * ---------------------------------------------------------
         * Index des jours fériés exacts
         * ---------------------------------------------------------
         *
         * Exemple :
         *
         * 2027-04-02 => Vendredi Saint
         */
        $holidaysByDate = $publicHolidays
            ->keyBy(function ($holiday) {

                return $holiday->date instanceof \Carbon\CarbonInterface
                    ? $holiday->date->format('Y-m-d')
                    : substr(
                        (string) $holiday->date,
                        0,
                        10
                    );
            });


        /*
         * ---------------------------------------------------------
         * Index des jours fériés récurrents
         * ---------------------------------------------------------
         *
         * Exemple :
         *
         * 12-25 => Noël
         *
         * L'année n'est donc plus prise en compte.
         */
        $recurringHolidays = $publicHolidays
            ->filter(function ($holiday) {

                return (bool) $holiday->is_recurring;
            })
            ->keyBy(function ($holiday) {

                $date = $holiday->date;

                if (
                    $date instanceof
                    \Carbon\CarbonInterface
                ) {
                    return $date->format('m-d');
                }

                return substr(
                    (string) $date,
                    5,
                    5
                );
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
         * Ce compteur est relatif à LA DEMANDE.
         *
         * samedi #1 = travaillé
         * samedi #2 = repos
         * samedi #3 = travaillé
         * samedi #4 = repos
         */
        $saturdayNumber = 0;


        foreach ($period as $date) {

            $date = $date
                ->copy()
                ->startOfDay();

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
                $workingDays->get(
                    $dayOfWeek
                );

            $isWorkingDay = false;

            $countsForLeave = false;

            $workingRatio = 0;


            if ($workingDay) {

                $isWorkingDay =
                    (bool)
                    $workingDay->is_working_day;

                $countsForLeave =
                    (bool)
                    $workingDay->counts_for_leave;

                $workingRatio =
                    (float)
                    $workingDay->working_ratio;
            }


            /*
             * -----------------------------------------------------
             * SAMEDI SUR DEUX
             * -----------------------------------------------------
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


                if (
                    $isAlternateSaturdayWorking
                ) {

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
             *
             * Priorité :
             *
             * 1. Correspondance exacte
             * 2. Correspondance récurrente MM-DD
             */
            $publicHoliday =
                $holidaysByDate->get(
                    $dateString
                );


            /*
             * Aucun jour férié exact :
             *
             * on recherche un récurrent
             * ayant le même mois et le même jour.
             */
            if (!$publicHoliday) {

                $monthDay =
                    $date->format('m-d');

                $publicHoliday =
                    $recurringHolidays->get(
                        $monthDay
                    );
            }


            /*
             * -----------------------------------------------------
             * Application du jour férié
             * -----------------------------------------------------
             */
            $isPublicHoliday =
                $publicHoliday !== null;

            $comment = null;


            if ($publicHoliday) {

                $comment =
                    $publicHoliday->name;


                /*
                 * Le jour férié peut être configuré
                 * pour ne pas compter dans les congés.
                 */
                if (
                    !$publicHoliday->counts_for_leave
                ) {

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