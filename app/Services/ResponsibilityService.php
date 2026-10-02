<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResponsibilityService
{
    /**
     * Vérifie si les responsabilités contiennent
     * au moins un des codes demandés.
     */
    public function hasAnyCode(array $responsibilities, array $codes): bool
    {
        if (empty($responsibilities) || empty($codes)) {
            return false;
        }

        $codes = array_map('strtoupper', $codes);

        foreach ($responsibilities as $responsibility) {
            $code = $responsibility['code'] ?? null;

            if ($code && in_array(strtoupper($code), $codes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si les responsabilités contiennent
     * tous les codes demandés.
     */
    public function hasAllCodes(array $responsibilities, array $codes): bool
    {
        if (empty($codes)) {
            return true;
        }

        $availableCodes = array_map(
            'strtoupper',
            array_column($responsibilities, 'code')
        );

        foreach ($codes as $code) {
            if (!in_array(strtoupper($code), $availableCodes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
 * Retourne les employés correspondant aux responsabilités demandées.
 *
 * @param array $responsibilityCodes
 * @param string $mode all|any
 * @return array
 */
public function findEmployeesByResponsibilities(
    array $responsibilityCodes,
    string $mode = 'any'
): array {

    $responsibilityCodes = array_values(
        array_filter(
            array_map('strtoupper', $responsibilityCodes)
        )
    );

    if (empty($responsibilityCodes)) {
        return [];
    }

    if (!in_array($mode, ['all', 'any'], true)) {
        throw new \InvalidArgumentException(
            "Le mode doit être 'all' ou 'any'."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Récupérer les responsabilités depuis le contexte utilisateur
    |--------------------------------------------------------------------------
    |
    | Ici, selon ton implémentation actuelle, on pourra remplacer cette
    | partie par l'appel HTTP au DepartmentService.
    |
    */

    // À adapter à ta méthode existante qui récupère les employés
    $employees = $this->getEmployeesWithResponsibilities(  $responsibilityCodes,
     $mode );

    return collect($employees)
        ->filter(function ($employee) use ($responsibilityCodes, $mode) {

            $responsibilities = collect(
                data_get($employee, 'responsibilities', [])
            );

            $codes = $responsibilities
                ->map(function ($responsibility) {
                    return strtoupper(
                        data_get($responsibility, 'code', '')
                    );
                })
                ->filter()
                ->values();

            if ($mode === 'all') {
                return collect($responsibilityCodes)
                    ->every(function ($code) use ($codes) {
                        return $codes->contains($code);
                    });
            }

            return collect($responsibilityCodes)
                ->contains(function ($code) use ($codes) {
                    return $codes->contains($code);
                });
        })
        ->values()
        ->all();
}

public function findEmployeeByResponsibilities(
    array $responsibilities,
    string $mode = 'any'
): ?array {


// throw new Exception(json_encode($responsibilities), 1);


    return $this->getEmployeesWithResponsibilities(
        $responsibilities,
        $mode
    )[0] ?? null;
}



protected function getEmployeesWithResponsibilities(
    array $responsibilities,
    string $mode = 'any'
): array {


// throw new Exception(json_encode(), 1);


    $response = Http::withToken(
        request()->bearerToken()
    )->get(
        config('services.department_service.base_url')
        . '/employees/responsibility-members-multiple',
        [
            'responsibilities' => $responsibilities,
            'mode' => $mode,
        ]
    );

    if (!$response->successful()) {


        throw new Exception(json_encode($response->body()), 1);


        Log::error(
            'Impossible de récupérer les employés par responsabilités',
            [
                'responsibilities' => $responsibilities,
                'mode' => $mode,
                'status' => $response->status(),
                'body' => $response->body(),
            ]
        );

        return [];
    }

    return $response->json('data', []);
}

}