<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP pour communiquer avec le microservice Département.
 *
 * Centralise la configuration HTTP et les appels vers le service.
 */
class DepartmentServiceClient
{
    /**
     * URL de base du microservice Département.
     *
     * @var string
     */
    protected string $baseUrl;

      /**
 * Cache mémoire des départements récupérés.
 *
 * Le cache est partagé pendant la durée de vie
 * de la classe dans le processus PHP courant.
 *
 * @var array<int, array|null>
 */
protected static $departmentCache = [];

    /**
     * Initialise le client avec l'URL configurée.
     */
    public function __construct()
    {
        $this->baseUrl = (string) config(
            'services.department_service.base_url'
        );
    }

    /**
     * Construit le client HTTP utilisé pour les requêtes.
     *
     * Le token Bearer de la requête entrante est transmis
     * au microservice Département.
     *
     * @return PendingRequest
     */
    protected function client(): PendingRequest
    {
        return Http::withToken(request()->bearerToken())
            ->acceptJson()
            ->baseUrl($this->baseUrl);
    }


    /**
 * Récupère les informations d'un département par son identifiant.
 *
 * Endpoint attendu :
 * GET /departments/{departmentId}
 *
 * @param int $departmentId
 * @return array|null
 *
 * @throws \Exception
 */
public function getDepartmentById(int $departmentId): ?array
{
    $response = $this->client()->get("/{$departmentId}");

    // Le département n'existe pas.
    if ($response->status() === 404) {

     throw new \Exception(
            'DepartmentService unavailable: ' . $response->body()
        );
        
        return null;
    }

    if ($response->failed()) {
        throw new \Exception(
            'DepartmentService unavailable: ' . $response->body()
        );
    }

    return $response->json('data') ?? $response->json();
}

/**
 * Récupère les informations d'un département avec cache mémoire.
 *
 * Si le département a déjà été récupéré, les informations
 * sont retournées depuis le cache sans nouvel appel HTTP.
 *
 * @param int $departmentId
 *
 * @return array|null
 */
public function resolveDepartment(int $departmentId): ?array
{
    /*
    |--------------------------------------------------------------------------
    | Clé du cache
    |--------------------------------------------------------------------------
    */

    $cacheKey = $departmentId;

    /*
    |--------------------------------------------------------------------------
    | Réutilisation du cache
    |--------------------------------------------------------------------------
    */

    if (array_key_exists($cacheKey, self::$departmentCache)) {
        Log::debug(
            'DepartmentServiceClient: département réutilisé depuis le cache.',
            [
                'department_id' => $departmentId,
            ]
        );

        return self::$departmentCache[$cacheKey];
    }

    /*
    |--------------------------------------------------------------------------
    | Appel au microservice Département
    |--------------------------------------------------------------------------
    */

    $response = $this->client()->get(
        "/{$departmentId}"
    );

    /*
    |--------------------------------------------------------------------------
    | Gestion des erreurs HTTP
    |--------------------------------------------------------------------------
    */

    if (!$response->successful()) {


        Log::warning(
            'DepartmentServiceClient: impossible de récupérer le département.',
            [
                'department_id' => $departmentId,
                'http_status' => $response->status(),
            ]
        );

        // On mémorise également l'absence de résultat afin
        // d'éviter de répéter un appel qui a échoué.
        self::$departmentCache[$cacheKey] = null;

        return null;
    }

    // throw new Exception(json_encode($response->json('data')), 1);


    /*
    |--------------------------------------------------------------------------
    | Extraction des informations
    |--------------------------------------------------------------------------
    |
    | Le microservice peut retourner les données dans :
    | - data
    | - department
    | - ou directement dans la réponse JSON.
    |
    */

    $department = $response->json('data')
        ?? $response->json('department')
        ?? $response->json();

    /*
    |--------------------------------------------------------------------------
    | Mise en cache
    |--------------------------------------------------------------------------
    */

    self::$departmentCache[$cacheKey] = is_array($department)
        ? $department
        : null;

    Log::debug(
        'DepartmentServiceClient: département chargé depuis le microservice.',
        [
            'department_id' => $departmentId,
        ]
    );

    return self::$departmentCache[$cacheKey];
}

    /**
     * Récupère les employés d'un département.
     *
     * Endpoint attendu :
     * GET /employees?department_id={departmentId}
     *
     * @param int $departmentId
     * @return array
     *
     * @throws \Exception
     */
    public function employeesByDepartment(int $departmentId): array
    {
        $response = $this->client()->get('/employees', [
            'department_id' => $departmentId,
        ]);

        if ($response->failed()) {
            throw new \Exception(
                'DepartmentService unavailable: ' . $response->body()
            );
        }

        return $response->json('data') ?? [];
    }
}