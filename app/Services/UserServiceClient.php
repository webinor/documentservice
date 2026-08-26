<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UserServiceClient
{
    protected $defaulUrl ;

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
        return $this->client()->post(
            "/events/dispatch/init-confirm-payment-receive",
            [
                "payload" => [
                    "actor" => $actor,
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


    public function resolveActor(string $type, int $id): ?array
    {
        $baseUrl = config("services.user_service.base_url");


        switch ($type) {

            case 'EMPLOYEE':
                $url = $baseUrl . "/employee/" . $id;
                break;

            case 'USER':
                $url = $baseUrl . "/users/" . $id;
                break;

            default:
                return null;
        }


        $response = Http::acceptJson()->get($url);


        if (!$response->successful()) {
            return null;
        }


        return $response->json('user') ?? $response->json('employee');
    }
}