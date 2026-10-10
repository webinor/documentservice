<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class WorkflowServiceClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.workflow_service.base_url'),
            '/'
        );
    }

    /**
     * Met à jour le statut de l'étape de règlement
     * d'une fiche de régularisation.
     *
     * @param string $documentUuid
     * @param string $statusCode
     * @return Response
     */
    public function markReceiptsReceivedWaitingClosure(
        string $documentUuid,
        string $statusCode
    ): Response {
        return Http::acceptJson()
            ->withToken(request()->bearerToken())
            ->timeout(15)
            ->post(
                $this->baseUrl
                    . '/workflow-instances/'
                    . $documentUuid
                    . '/receipts-received-waiting-closure',
                [
                    'statusCode' => $statusCode,
                ]
            );
    }
}