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

class LeaveCalculatorService
{

    protected AbsenceRequest $absence;

    protected LeaveCalculationRequest $request;

    protected $leaveType;

    protected $rule;

    protected $calendar;

    protected Collection $workingDays;

    protected Collection $publicHolidays;

    protected Collection $days;


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

        $this->applyPublicHolidays();

        $this->applyLeaveRule();

        return $this->buildResult();

    }



    /**
     * Chargement des règles RH
     */
    protected function loadConfiguration()
    {

        // $this->leaveType = $this->absence
        //     ->leaveType;


        $this->leaveType = LeaveType::with('rule')->findOrFail($this->request->leaveTypeId);

        // throw new \Exception(json_encode($this->leaveType), 1);
        

        $this->rule= $this->leaveType->rule;


        /**
         * Calendrier par défaut
         * A remplacer plus tard par celui
         * de l'organisation
         */
        $this->calendar = WorkCalendar::where(
                'is_default',
                true
            )
            ->firstOrFail();



        /**
         * Jours ouvrés
         */
        $this->workingDays =
            WorkCalendarWorkingDay::where(
                'work_calendar_id',
                $this->calendar->id
            )
            ->get()
            ->keyBy('day_of_week');




        /**
         * Jours fériés
         */
        $this->publicHolidays =
            PublicHoliday::where(
                'work_calendar_id',
                $this->calendar->id
            )
            ->get()
            ->keyBy(function($holiday){

                return Carbon::parse(
                    $holiday->date
                )->format('Y-m-d');

            });


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

    }





    /**
     * Application du calendrier de travail
     */
    protected function applyWorkingCalendar()
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





    /**
     * Application des jours fériés
     */
    protected function applyPublicHolidays()
    {


        $this->days =
            $this->days->map(function($day){


                if(
                    $this->publicHolidays
                    ->has($day['date'])
                )
                {

                    $holiday =
                        $this->publicHolidays
                        ->get($day['date']);



                    $day['is_public_holiday']=true;



                    if(
                        !$holiday->counts_for_leave
                    )
                    {
                        $day['counts_for_leave']=false;
                    }


                    $day['comment'] =
                        $holiday->name;

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