<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;

class EmployeeServiceClient
{
    public function deductLeaveDays(array $payload): array
    {
        $response = Http::withToken(request()->bearerToken())
            ->acceptJson()
            ->post(
                config('services.user_service.base_url') .
                '/leave-balances/deduct',
                $payload
            );

        if (!$response->successful()) {
            throw new \Exception(
                'EmployeeService error: ' . $response->body()
            );
        }

        return $response->json();
    }
}