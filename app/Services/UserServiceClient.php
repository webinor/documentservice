<?php

namespace App\Services;

// use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserServiceClient
{
    protected string $defaulUrl ;
    protected static $actorCache = [];

    public function __construct() {
        $this->defaulUrl =  config("services.user_service.base_url");
    }
    protected function client($url = null)
    {
        if (!$url) {
           $url = $this->defaulUrl;
        }
        return Http::withToken(request()->bearerToken())
            ->acceptJson()
            ->baseUrl($url);
    }

    public function OldhasPermissions(
    array $document,
    array $user,
    array $actions
): bool {

    $userServiceUrl = config(
        "services.user_service.base_url"
    );

    $response = Http::withToken(
        request()->bearerToken()
    )
    ->acceptJson()
    ->post(
        $userServiceUrl . "/permissions/check-batch",
        [
            "userId" => $user["id"],

            "documents" => [
                [
                    "id" => $document["document_type_id"],
                    "type" => $document["document_type"]["name"],
                ]
            ],

            "actions" => $actions,
        ]
    );

    if ($response->failed()) {
        return false;
    }

    $result = $response->json();

    return data_get(
        $result,
        "0.permissions.bypass",
        false
    );
}

public function getLeaveTransactionByAbsenceId(
    int $absenceRequestId
): ?array {

    $response = $this->client()->get(
        "/leave-transactions/absence/{$absenceRequestId}"
    );

    if ($response->status() === 404) {
        return null;
    }

    if ($response->failed()) {
        throw new \Exception(
            "UserService unavailable: " . $response->body()
        );
    }

    return $response->json('data');
}

public function hasPermissions(
    array $document,
    array $user,
    array $actions,
    string $mode = 'all'
): bool {

    if (empty($actions)) {
        return false;
    }

    if (!in_array($mode, ['all', 'any'], true)) {
        throw new \InvalidArgumentException(
            "Permission mode must be 'all' or 'any'."
        );
    }

    $userServiceUrl = config(
        "services.user_service.base_url"
    );

    $response = Http::withToken(
        request()->bearerToken()
    )
    ->acceptJson()
    ->post(
        $userServiceUrl . "/permissions/check-batch",
        [
            "userId" => $user["id"],

            "documents" => [
                [
                    "id" => $document["document_type_id"],
                    "type" => $document["document_type"]["name"],
                ]
            ],

            "actions" => $actions,
        ]
    );

    if ($response->failed()) {
        return false;
    }

    $permissions = data_get(
        $response->json(),
        "0.permissions",
        []
    );

    if ($mode === 'all') {
        foreach ($actions as $action) {
            if (!($permissions[$action] ?? false)) {
                return false;
            }
        }

        return true;
    }

    // any
    foreach ($actions as $action) {
        if ($permissions[$action] ?? false) {
            return true;
        }
    }

    return false;
}


    public function getUser(int $userId)
    {
        return $this->client()->get("/{$userId}");
    }

    public function getUsersSignatures(array $userIds): array
{
    if (empty($userIds)) {
        return [];
    }

    $response = $this->client()->post(
        '/signatures/batch',
        [
            'user_ids' => array_values(
                array_unique($userIds)
            ),
        ]
    );

    if ($response->failed()) {
        throw new \Exception(
            'UserService unavailable: ' .
            $response->body()
        );
    }

    return $response->json('data') ?? [];
}

    public function getEmployeeIdByUser(int $userId): ?int
{
    $response = $this->getUser($userId);

    if ($response->failed()) {
        return null;
    }

    $data = $response->json();

    return data_get($data, 'employee_id')
        ?? data_get($data, 'user.employee_id')
        ?? data_get($data, 'data.employee_id')
        ?? data_get($data, 'data.user.employee_id');
}


    public function employeesByDepartment(int $departmentId): array
    {
      $response = $this->client(config("services.department_service.base_url"))
    ->get("/employees", [
        "department_id" => $departmentId
    ]);


        if ($response->failed()) {
            throw new \Exception("UserService unavailable".($response->body()));
        }


        return $response->json()['data'] ?? [];
    }

    public function employeesByCity(string $cityId): array
{
    $response = $this->client(
        config("services.user_service.base_url")
    )->get("/employees/by-city/$cityId");

    if ($response->failed()) {
        throw new \Exception(
            "UserService unavailable: " . $response->body()
        );
    }

    return $response->json()['data'] ?? [];
}


    public function dispatchPaymentEvent(
        array $actor,
        int $amount,
        string $reason,
        string $direction,
        string $transactionTypeCode,
        int $document_id,
        string $document_uuid,
        array $details
    )
    {

       $user = request()->get("user");

        $userId = $user["id"];

        // throw new \Exception($userId, 1);
        

        return $this->client()->post(
            "/events/dispatch/init-confirm-payment-receive",
            [
                "payload" => [
                    "actor" => $actor,
                    "user_id" => $userId,
                    "amount" => abs($amount),
                    "reason" => $reason,
                    "direction" => $direction,
                    "transactionTypeCode" => $transactionTypeCode,
                    "document_id" => $document_id,
                    "document_uuid" => $document_uuid,
                    "details" => $details
                ]
            ]
        );
    }


    public function getDocumentTransactions(int $documentId)
    {
        $response = $this->client()
            ->get("/documents/{$documentId}/transactions");


        if ($response->failed()) {
            throw new \Exception("UserService unavailable");
        }


        return $response->json()['data'] ?? [];
    }

      /**
 * Récupère les transactions de plusieurs documents
 * en une seule requête vers le UserService.
 *
 * @param array<int, int> $documentIds
 *
 * @return array
 */
public function getDocumentsTransactions(
    array $documentIds
): array {
    if (empty($documentIds)) {
        return [];
    }

    $documentIds = array_values(
        array_unique(
            array_map('intval', $documentIds)
        )
    );

    $response = $this->client()->post(
        '/documents/transactions/batch',
        [
            'document_ids' => $documentIds,
        ]
    );

    if ($response->failed()) {
        throw new \Exception(
            'UserService unavailable: ' .
            $response->body()
        );
    }

    return $response->json('data') ?? [];
}


    public function OldresolveActor(string $type, int $id): ?array
    {
        $baseUrl = config("services.user_service.base_url");


        switch ($type) {

            case 'EMPLOYEE':
                $url = $baseUrl . "/employee/" . $id;
                break;

            case 'USER':
                $url = $baseUrl . "/" . $id;
                break;

            default:
                return null;
        }


        $response = Http::acceptJson()->get($url);


        if (!$response->successful()) {


            // throw new Exception(json_encode($response->body()), 1);
                


            return null;
        }


        return $response->json('user') ?? $response->json('employee');
    }

/**
 * Résout un acteur avec cache mémoire.
 *
 * Le cache est partagé pendant toute la durée
 * de la requête PHP.
 *
 * Ainsi, si 50 documents utilisent le même
 * acteur, UserService n'est appelé qu'une seule fois.
 *
 * @param string $type
 * @param int $id
 *
 * @return array|null
 */
public function resolveActor(
    string $type,
    int $id
): ?array {
    $cacheKey =
        strtoupper($type) . ':' . $id;

    /*
    |--------------------------------------------------------------------------
    | Réutilisation du cache
    |--------------------------------------------------------------------------
    |
    | Si l'acteur existe déjà dans le cache, aucune requête
    | HTTP vers le UserService n'est effectuée.
    |
    | On écrit un log afin de pouvoir vérifier concrètement
    | pendant l'export que le cache est bien réutilisé.
    |
    */

    if (array_key_exists(
        $cacheKey,
        self::$actorCache
    )) {
        Log::debug(
            'UserServiceClient: acteur réutilisé depuis le cache.',
            [
                'cache_key' => $cacheKey,
                'actor_type' => strtoupper($type),
                'actor_id' => $id,
            ]
        );

        return self::$actorCache[$cacheKey];
    }

    /*
    |--------------------------------------------------------------------------
    | Construction de l'URL
    |--------------------------------------------------------------------------
    */

    $baseUrl =
        config("services.user_service.base_url");

    switch ($type) {
        case 'EMPLOYEE':
            $url =
                $baseUrl . "/employee/" . $id;
            break;

        case 'USER':
            $url =
                $baseUrl . "/" . $id;
            break;

        default:
            self::$actorCache[$cacheKey] = null;

            return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Appel UserService
    |--------------------------------------------------------------------------
    */

    $response =
        Http::withToken(request()->bearerToken())
            ->acceptJson()
            ->get($url);

    if (!$response->successful()) {
        self::$actorCache[$cacheKey] = null;

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Préparation de l'acteur
    |--------------------------------------------------------------------------
    */

    $actor =
        $response->json('user')
        ?? $response->json('employee');

    /*
    |--------------------------------------------------------------------------
    | Mise en cache
    |--------------------------------------------------------------------------
    */

    self::$actorCache[$cacheKey] = $actor;

    Log::debug(
        'UserServiceClient: acteur chargé depuis le UserService et mis en cache.',
        [
            'cache_key' => $cacheKey,
            'actor_type' => strtoupper($type),
            'actor_id' => $id,
        ]
    );

    return $actor;
}
}