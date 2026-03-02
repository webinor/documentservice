<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UserServiceClient
{
    protected function client()
    {
        return Http::withToken(request()->bearerToken())
            ->acceptJson()
            ->baseUrl(config("services.user_service.base_url"));
    }

    public function getUser(int $userId)
    {
        return $this->client()->get("/{$userId}");
    }

    public function dispatchPaymentEvent(int $beneficiaryId, int $amount , string $reason)
    {
        return $this->client()->post(
            "/events/dispatch/init-confirm-payment-receive",
            [
                "payload" => [
                    "beneficiary" => $beneficiaryId,
                    "amount" => $amount,
                    "reason" => $reason,
                ]
            ]
        );
    }
}