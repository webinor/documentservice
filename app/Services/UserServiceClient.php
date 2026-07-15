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
    

    public function dispatchPaymentEvent(array $actor, int $amount , string $reason , string $direction,string $transactionTypeCode , int $document_id , array $details)
    {
        
        
        return $this->client()->post("/events/dispatch/init-confirm-payment-receive",
            [
                "payload" => [
                    "actor" => $actor,
                    "amount" => abs($amount),
                    "reason" => $reason,
                    'direction' => $direction,
                    'transactionTypeCode' => $transactionTypeCode,
                    'document_id'=>$document_id,
                    'details'=>$details
                ]
            ]
        );
    }

    public function getDocumentTransactions(int $documentId)
    {
        // $baseUrl = config('services.user_service.base_url');

        $response = $this->client()->get("/documents/{$documentId}/transactions");

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