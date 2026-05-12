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

    public function dispatchPaymentEvent(int $actor_id, int $amount , string $reason , string $direction,string $transactionTypeCode , int $document_id)
    {
        return $this->client()->post("/events/dispatch/init-confirm-payment-receive",
            [
                "payload" => [
                    "actor_id" => $actor_id,
                    "amount" => $amount,
                    "reason" => $reason,
                    'direction' => $direction,
                    'transactionTypeCode' => $transactionTypeCode,
                    'document_id'=>$document_id
                ]
            ]
        );
    }
}