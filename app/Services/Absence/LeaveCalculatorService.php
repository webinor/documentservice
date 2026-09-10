<?php

namespace App\Services\Absence;

use App\DTO\LeaveCalculationRequest;
use App\Models\AbsenceRequest;
use App\Models\LeaveType;
use App\Models\WorkCalendar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class LeaveCalculatorService
{
    protected AbsenceRequest $absence;

    protected LeaveCalculationRequest $request;

    protected WorkCalendarResolver $calendarResolver;

    protected LeaveType $leaveType;

    protected $rule;

    protected $calendar;

    protected Collection $days;

    public function __construct(
        WorkCalendarResolver $calendarResolver
    ) {
        $this->calendarResolver = $calendarResolver;
    }

    /**
     * Calcul de base de la demande d'absence.
     *
     * Cette méthode ne récupère PAS le solde.
     */
    public function calculate(
        LeaveCalculationRequest $request
    ): array {

        $this->request = $request;

        $this->days = collect();

        $this->loadConfiguration();

        $this->buildDays();

        $this->applyWorkingCalendar();

        $this->applyLeaveRule();

        return $this->buildResult();
    }

    /**
     * Calcul complet avec récupération du solde.
     *
     * Cette méthode est utilisée aussi bien par :
     *
     * - LeaveSimulationController
     * - AbsenceDocumentEnrichmentHandler
     */
    public function calculateWithBalance(
        LeaveCalculationRequest $request,
        ?string $token = null
    ): array {

        $result = $this->calculate($request);

        /*
         * Récupération du solde de congés.
         */
        $http = Http::acceptJson();

        if ($token) {
            $http->withToken($token);
        }

        $balanceResponse = $http->get(
            config('services.user_service.base_url')
                . '/leave-balances/'
                . $request->employeeId,
            [
                'year' => Carbon::parse(
                    $request->startDate
                )->year,

                    'date' => Carbon::parse(
            $request->startDate
        )->toDateString(),
            ]
        );

        if (!$balanceResponse->successful()) {
            throw new \RuntimeException(
                "Impossible de récupérer le solde de congés de l\'employe {$request->employeeId} : ".$balanceResponse->body()
            );
        }

        $balance = $balanceResponse->json();

        $availableBalance =
            (float) (
                $balance['remaining_days'] ?? 0
            );

        $deductDays =
            (float) (
                $result['summary']['deduct_days'] ?? 0
            );

        $result['summary']['available_balance'] =
            $availableBalance;

        $result['summary']['remaining_balance'] =
            $availableBalance - $deductDays;

        return $result;
    }

    /**
     * Chargement des règles RH.
     */
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
         * Calendrier par défaut.
         */
        $this->calendar =
            WorkCalendar::where(
                'is_default',
                true
            )
            ->firstOrFail();
    }

    /**
     * Génération de toutes les dates demandées.
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

        foreach ($period as $date) {

            $this->days->push([

                'date' =>
                    $date->format('Y-m-d'),

                'day_name' =>
                    $date
                        ->locale('fr')
                        ->dayName,

                'day_of_week' =>
                    $date->dayOfWeekIso,

                'is_working_day' =>
                    false,

                'is_public_holiday' =>
                    false,

                'coverage_type' =>
                    null,

                'deducts_balance' =>
                    false,

                'deduct_days' =>
                    0,

                'comment' =>
                    null,
            ]);
        }
    }

    /**
     * Application du calendrier de travail.
     */
    protected function applyWorkingCalendar()
    {
        $resolvedDays =
            $this->calendarResolver->resolvePeriod(
                $this->calendar,
                Carbon::parse(
                    $this->request->startDate
                ),
                Carbon::parse(
                    $this->request->endDate
                )
            );

        $this->days =
            $resolvedDays->map(function ($day) {

                $day['coverage_type'] =
                    null;

                $day['deducts_balance'] =
                    false;

                $day['deduct_days'] =
                    0;

                return $day;
            });
    }

    /**
     * Application des règles du type de congé.
     */
    protected function applyLeaveRule()
    {
        $eligibleDays =
            $this->days
                ->filter(function ($day) {

                    return $day[
                        'counts_for_leave'
                    ] ?? false;
                });

        /*
         * Nombre de jours éligibles.
         */
        $balanceDays =
            $eligibleDays->count();

        /*
         * Nombre de jours payés
         * par la règle.
         */
        $paidDays = 0;

        if (
            $this->rule &&
            $this->rule->paid_days !== null
        ) {

            $paidDays = min(
                $this->rule->paid_days,
                $balanceDays
            );
        }

        $remainingPaidDays =
            $paidDays;

        $this->days =
            $this->days->map(
                function ($day) use (
                    &$remainingPaidDays
                ) {

                    /*
                     * Jour exclu :
                     *
                     * dimanche
                     * jour férié
                     * samedi non travaillé
                     * etc.
                     */
                    if (
                        !(
                            $day[
                                'counts_for_leave'
                            ] ?? false
                        )
                    ) {

                        $day[
                            'coverage_type'
                        ] =
                            'NON_WORKING';

                        $day[
                            'deducts_balance'
                        ] =
                            false;

                        $day[
                            'deduct_days'
                        ] =
                            0;

                        return $day;
                    }

                    /*
                     * Jour couvert gratuitement.
                     */
                    if (
                        $remainingPaidDays > 0
                    ) {

                        $day[
                            'coverage_type'
                        ] =
                            'EXCEPTIONAL_PAID';

                        $day[
                            'deducts_balance'
                        ] =
                            false;

                        $day[
                            'deduct_days'
                        ] =
                            0;

                        $remainingPaidDays--;

                    } else {

                        /*
                         * Jour imputé au solde annuel.
                         */
                        $day[
                            'coverage_type'
                        ] =
                            'ANNUAL_BALANCE';

                        $day[
                            'deducts_balance'
                        ] =
                            true;

                        $day[
                            'deduct_days'
                        ] =
                            1;
                    }

                    return $day;
                }
            );
    }

    /**
     * Construction du résultat.
     */
    protected function buildResult(): array
    {
        return [

            'summary' => [

                'requested_days' =>
                    $this->days->count(),

                'working_days' =>
                    $this->days
                        ->where(
                            'counts_for_leave',
                            true
                        )
                        ->count(),

                'paid_days' =>
                    $this->days
                        ->where(
                            'coverage_type',
                            'EXCEPTIONAL_PAID'
                        )
                        ->count(),

                'balance_days' =>
                    $this->days
                        ->where(
                            'coverage_type',
                            'ANNUAL_BALANCE'
                        )
                        ->count(),

                'unpaid_days' =>
                    $this->days
                        ->where(
                            'coverage_type',
                            'UNPAID'
                        )
                        ->count(),

                'deduct_days' =>
                    $this->days
                        ->sum('deduct_days'),
            ],

            'days' =>
                $this->days->values(),
        ];
    }
}