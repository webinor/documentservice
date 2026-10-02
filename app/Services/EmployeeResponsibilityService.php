<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EmployeeResponsibilityService
{
    private ResponsibilityService $responsibilityService;

    protected UserServiceClient $userServiceClient;

    public function __construct(
        ResponsibilityService $responsibilityService,
        UserServiceClient $userServiceClient
    ) {
        $this->responsibilityService = $responsibilityService;
        $this->userServiceClient = $userServiceClient;
    }

    /**
     * Récupère les employés possédant au moins
     * une des responsabilités demandées puis
     * enrichit chaque employé avec les données
     * provenant du UserService.
     *
     * Supporte deux formats retournés par
     * ResponsibilityService :
     *
     * Format 1 :
     *
     * [
     *     12,
     *     25,
     *     31
     * ]
     *
     * Format 2 :
     *
     * [
     *     [
     *         'employee_id' => 12,
     *         'responsibilities' => [...]
     *     ],
     *     [
     *         'employee_id' => 25,
     *         'responsibilities' => [...]
     *     ]
     * ]
     *
     * @param array $responsibilities
     * @return array
     */
    public function getByResponsibilities(
        array $responsibilities
    ): array {
        if (empty($responsibilities)) {
            return [];
        }

        /*
         * 1. Récupération des employés depuis
         *    le DepartmentService via ResponsibilityService.
         */
        $departmentEmployees = $this->responsibilityService
            ->findEmployeeByResponsibilities(
                $responsibilities
            );

        if (empty($departmentEmployees)) {
            return [];
        }

        /*
         * 2. Normalisation + enrichissement.
         */
        return collect($departmentEmployees)
            ->map(function ($departmentEmployee) {

                /*
                 * ---------------------------------------------------------
                 * FORMAT 1 :
                 * ---------------------------------------------------------
                 *
                 * [
                 *     12,
                 *     25,
                 *     31
                 * ]
                 *
                 * Dans ce cas, on transforme l'ID en structure standard.
                 */
                if (is_numeric($departmentEmployee)) {
                    $departmentEmployee = [
                        'employee_id' => (int) $departmentEmployee,
                    ];
                }

                /*
                 * ---------------------------------------------------------
                 * FORMAT 2 :
                 * ---------------------------------------------------------
                 *
                 * [
                 *     'employee_id' => 12,
                 *     ...
                 * ]
                 */
                if (is_array($departmentEmployee)) {
                    return $this->enrichEmployee(
                        $departmentEmployee
                    );
                }

                /*
                 * Format inattendu.
                 */
                Log::warning(
                    'EmployeeResponsibilityService : format employé inattendu.',
                    [
                        'employee' => $departmentEmployee,
                    ]
                );

                return null;
            })
            ->filter()
            ->unique(function (array $employee) {
                return data_get(
                    $employee,
                    'employee_id'
                );
            })
            ->values()
            ->all();
    }

    /**
     * Retourne le premier employé trouvé
     * pour les responsabilités demandées.
     *
     * Exemple :
     *
     * $director = $service->findByResponsibilities([
     *     'CEO',
     *     'DIRECTOR'
     * ]);
     */
    public function findByResponsibilities(
        array $responsibilities
    ): ?array {
        $employees = $this->getByResponsibilities(
            $responsibilities
        );

        return $employees[0] ?? null;
    }

    /**
     * Enrichit un employé retourné par
     * DepartmentService avec les informations
     * provenant du UserService.
     */
    protected function enrichEmployee(
        array $departmentEmployee
    ): ?array {
        /*
         * Récupération de l'identifiant employé.
         */
        $employeeId = data_get(
            $departmentEmployee,
            'employee_id'
        );

        if (!$employeeId) {
            Log::warning(
                'EmployeeResponsibilityService : employee_id absent.',
                [
                    'employee' => $departmentEmployee,
                ]
            );

            return null;
        }

        /*
         * Récupération des informations de l'employé
         * depuis le UserService.
         */
        $employee = $this->userServiceClient
            ->resolveActor(
                'EMPLOYEE',
                (int) $employeeId
            );

        if (!$employee) {
            Log::warning(
                'Impossible de récupérer les informations de l’employé dans UserService.',
                [
                    'employee_id' => $employeeId,
                ]
            );

            return null;
        }

        /*
         * On conserve les informations provenant
         * du DepartmentService :
         *
         * - employee_id
         * - active_department_position
         * - responsibilities
         *
         * et on ajoute :
         *
         * - employee
         *
         * provenant du UserService.
         */
        return $employee;
        return array_merge(
            $departmentEmployee,
            [
                'employee' => $employee,
            ]
        );
    }
}