<?php

namespace App\Services;

use App\Contracts\WorkflowAvailabilityClient;
use Illuminate\Support\Facades\Http;

class WorkflowAvailabilityHttpClient implements WorkflowAvailabilityClient
{
    public function getDocumentContext(int $documentId): array
    {
        $response = Http::acceptJson()
            ->withHeaders([
                'X-SERVICE-TOKEN' => config('services.workflow.token'),
            ])
            ->get(
                config('services.workflow.base_url')
                . "/documents/{$documentId}/availability-context"
            );

        if (!$response->successful()) {
            throw new \Exception(
                "Impossible de récupérer le contexte workflow."
            );
        }

        return $response->json();
    }
}