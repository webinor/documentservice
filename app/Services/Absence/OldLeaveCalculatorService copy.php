<?php

namespace App\Services\Absence;

use App\DTO\LeaveCalculationRequest;
use App\Models\AbsenceRequest;
use App\Models\LeaveType;
use App\Models\LeaveTypeRule;
use App\Models\WorkCalendar;
use App\Models\WorkCalendarWorkingDay;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class OldLeaveCalculatorService
{

    protected AbsenceRequest $absence;

    protected LeaveCalculationRequest $request;

    protected WorkCalendarResolver $calendarResolver;

    protected LeaveType $leaveType;

    protected  $rule;

    protected $calendar;

    protected Collection $workingDays;

    protected Collection $publicHolidays;

    protected Collection $days;

    public function __construct(
    WorkCalendarResolver $calendarResolver
) {
    $this->calendarResolver =
        $calendarResolver;
}


    /**
     * Calcul complet d'une demande d'absence
     */
    public function calculate(LeaveCalculationRequest $request): array
    {

        // $this->absence = $absence;
        $this->request = $request;

        // $this->leaveType =LeaveType::findOrFail($request->leaveTypeId);

        $this->days = collect();

        $this->loadConfiguration();

        $this->buildDays();

        $this->applyWorkingCalendar();

        // $this->applyPublicHolidays();

        $this->applyLeaveRule();

        return $this->buildResult();

    }



 

    protected function loadConfiguration()
{
    $this->leaveType =
        LeaveType::with('rule')
            ->findOrFail(
                $this->request->leaveTypeId
            );


    $this->rule =
        $this->leaveType->rule;


    /*
     * Calendrier par défaut
     */
    $this->calendar =
        WorkCalendar::where(
            'is_default',
            true
        )
        ->firstOrFail();
}







    /**
     * Génération de toutes les dates demandées
     */
    protected function buildDays()
    {


        $period = CarbonPeriod::create(

    Carbon::parse(
        $this->request->startDate
    ),

    Carbon::parse(
        $this->request->endDate
    )

);



        foreach($period as $date)
        {


            $this->days->push([

                'date' => $date->format('Y-m-d'),

                'day_name' => $date
                    ->locale('fr')
                    ->dayName,


                /**
                 * ISO :
                 * lundi = 1
                 * dimanche = 7
                 */
                'day_of_week' => 
                    $date->dayOfWeekIso,


                'is_working_day'=>false,

                'is_public_holiday'=>false,


                'coverage_type'=>null,


                'deducts_balance'=>false,


                'deduct_days'=>0,


                'comment'=>null,

            ]);

        }

        // TEST TEMPORAIRE
    // throw new \Exception(
    //     json_encode([
    //         'start' => $this->request->startDate,
    //         'end' => $this->request->endDate,
    //         'count' => $this->days->count(),
    //         'dates' => $this->days->pluck('date')->values(),
    //     ], JSON_PRETTY_PRINT)
    // );

    }





    /**
     * Application du calendrier de travail
     */
    protected function OldapplyWorkingCalendar()
    {


        $this->days = $this->days->map(function($day){


            $workingDay =
                $this->workingDays
                    ->get($day['day_of_week']);



            if($workingDay)
            {

                $day['is_working_day'] =
                    (bool)$workingDay->is_working_day;


                $day['counts_for_leave'] =
                    (bool)$workingDay->counts_for_leave;

            }



            return $day;


        });


    }

 protected function applyWorkingCalendar()
{
    $resolvedDays =
        $this->calendarResolver->resolvePeriod(
            $this->calendar,
            Carbon::parse($this->request->startDate),
            Carbon::parse($this->request->endDate)
        );

    $this->days =
        $resolvedDays->map(function ($day) {

            $day['coverage_type'] = null;

            $day['deducts_balance'] = false;

            $day['deduct_days'] = 0;

            return $day;
        });
}





    /**
     * Application des jours fériés
     */
protected function OldapplyPublicHolidays()
{
    $this->days = $this->days->map(function ($day) {

        $date = $day['date'];

        /*
         * 1. Recherche exacte
         *
         * Exemple :
         * 2026-12-25 -> 2026-12-25
         */
        $holiday = $this->publicHolidays->get($date);

        /*
         * 2. Si aucun jour férié exact n'est trouvé,
         *    rechercher un jour férié récurrent
         *    ayant le même mois et le même jour.
         *
         * Exemple :
         *
         * BDD :
         * 2026-12-25 / Noël / is_recurring = true
         *
         * Jour calculé :
         * 2027-12-25
         *
         * => reconnu comme Noël
         */
        if (!$holiday) {

            $dayMonth = substr($date, 5, 5);

            $holiday = $this->publicHolidays
                ->first(function ($holiday) use ($dayMonth) {

                    if (!$holiday->is_recurring) {
                        return false;
                    }

                    $holidayDate = $holiday->date;

                    /*
                     * Le cast Laravel peut retourner
                     * un Carbon.
                     */
                    if ($holidayDate instanceof \Carbon\CarbonInterface) {
                        $holidayDate =
                            $holidayDate->format('Y-m-d');
                    } else {
                        $holidayDate =
                            substr((string) $holidayDate, 0, 10);
                    }

                    return substr($holidayDate, 5, 5) === $dayMonth;
                });
        }

        /*
         * Aucun jour férié trouvé.
         */
        if (!$holiday) {
            return $day;
        }

        /*
         * Jour férié détecté.
         */
        $day['is_public_holiday'] = true;

        $day['comment'] = $holiday->name;

        /*
         * Règle du type de congé.
         */
        $settings = $this->rule->settings ?? [];

        $countPublicHolidays =
            $settings['count_public_holidays']
            ?? false;

        if (!$countPublicHolidays) {
            $day['counts_for_leave'] = false;
        }

        return $day;
    });
}

protected function applyPublicHolidays()
{
    /*
     * Jours fériés correspondant exactement à la date.
     */
    $holidaysByDate = $this->publicHolidays
        ->keyBy(function ($holiday) {

            return $holiday->date instanceof \Carbon\CarbonInterface
                ? $holiday->date->format('Y-m-d')
                : substr((string) $holiday->date, 0, 10);
        });

    /*
     * Jours fériés récurrents indexés par MM-DD.
     */
    $recurringHolidays = $this->publicHolidays
        ->filter(function ($holiday) {
            return (bool) $holiday->is_recurring;
        })
        ->keyBy(function ($holiday) {

            $date = $holiday->date;

            if ($date instanceof \Carbon\CarbonInterface) {
                return $date->format('m-d');
            }

            return substr(
                (string) $date,
                5,
                5
            );
        });

    $this->days = $this->days->map(function ($day) use (
        $holidaysByDate,
        $recurringHolidays
    ) {

        $date = $day['date'];

        /*
         * Priorité à la correspondance exacte.
         */
        $holiday = $holidaysByDate->get($date);

        /*
         * Sinon, rechercher le même MM-DD
         * parmi les jours récurrents.
         */
        if (!$holiday) {

            $monthDay = substr(
                $date,
                5,
                5
            );

            $holiday =
                $recurringHolidays->get(
                    $monthDay
                );
        }

        /*
         * Aucun jour férié.
         */
        if (!$holiday) {
            return $day;
        }

        /*
         * Jour férié trouvé.
         */
        $day['is_public_holiday'] = true;

        $day['comment'] = $holiday->name;

        /*
         * Règle du type de congé.
         */
        $settings =
            $this->rule->settings ?? [];

        $countPublicHolidays =
            $settings[
                'count_public_holidays'
            ] ?? false;

        if (!$countPublicHolidays) {
            $day['counts_for_leave'] = false;
        }

        return $day;
    });
}


   /**
 * Application des règles du type de congé
 */
protected function applyLeaveRule()
{
    $eligibleDays =
        $this->days
            ->filter(function ($day) {
                return $day['counts_for_leave'] ?? false;
            });


    /**
     * Nombre de jours imputables
     */
    $balanceDays = $eligibleDays->count();


    /**
     * Nombre de jours payés par la règle
     */
    $paidDays = 0;


    if ($this->rule && $this->rule->paid_days !== null) {

    // throw new \Exception(json_encode($this->rule->paid_days), 1);
    // throw new \Exception(json_encode($balanceDays), 1);
    
        $paidDays = min(
            $this->rule->paid_days,
            $balanceDays
        );

    // throw new \Exception(json_encode($paidDays), 1);


    }
    else{

    // throw new \Exception(json_encode($this->rule), 1);


    }


    $remainingPaidDays = $paidDays;


    $this->days = $this->days->map(function ($day) use (&$remainingPaidDays) {


         /**
     * Jour exclu (dimanche, férié...)
     */
    if (!($day['counts_for_leave'] ?? false)) {

        $day['coverage_type'] = 'NON_WORKING';
        $day['deducts_balance'] = false;
        $day['deduct_days'] = 0;

        return $day;
    }


        /**
         * Jours couverts gratuitement
         */
        if ($remainingPaidDays > 0) {

            $day['coverage_type'] = 'EXCEPTIONAL_PAID';

            $day['deducts_balance'] = false;

            $day['deduct_days'] = 0;

            $remainingPaidDays--;

        }


        /**
         * Jours déduits du solde
         */
        else {

            $day['coverage_type'] = 'ANNUAL_BALANCE';

            $day['deducts_balance'] = true;

            $day['deduct_days'] = 1;

        }


        return $day;

    });

}





    /**
     * Résultat final
     */
    protected function buildResult(): array
    {


        return [

         'summary' => [

            'requested_days'=>
                $this->days->count(),



            'working_days'=>
                $this->days
                ->where(
                    'counts_for_leave',
                    true
                )
                ->count(),



            'paid_days'=>
                $this->days
                ->where(
                    'coverage_type',
                    'EXCEPTIONAL_PAID'
                )
                ->count(),



            'balance_days'=>
                $this->days
                ->where(
                    'coverage_type',
                    'ANNUAL_BALANCE'
                )
                ->count(),



            'unpaid_days'=>
                $this->days
                ->where(
                    'coverage_type',
                    'UNPAID'
                )
                ->count(),



            'deduct_days'=>
                $this->days
                ->sum('deduct_days'),

         ],

            'days'=>
                $this->days->values()

        ];

    }

}