<?php

namespace App\Services\Workflow;

use Exception;
use Illuminate\Support\Facades\Http;

class WorkflowParticipantService
{
    public function getParticipants($document, string $token): array
    {

            // throw new Exception(json_encode($document['document_type']));

        $response = Http::withToken($token)
            ->acceptJson()
            ->get(
                config('services.workflow_service.base_url') .
                "/documents/{$document['id']}/participants",
                [
                    'document_type' => $document['document_type']['slug'],
                ]
            );

        if (!$response->ok()) {
            throw new Exception("Error While retrieving participants");
        }

        return [
            'participants' => $response->json('participants'),
            'business_signatures' => $response->json('business_signatures'),
        ];
    }
}