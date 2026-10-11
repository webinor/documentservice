<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use RuntimeException;

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

    /**
     * Récupère les IDs des documents clôturés pendant une période.
     *
     * Les dates sont facultatives :
     * - dateStart seule : à partir de cette date ;
     * - dateEnd seule : jusqu'à cette date ;
     * - les deux dates : entre ces deux dates.
     *
     * @param string|null $dateStart
     * @param string|null $dateEnd
     * @return array
     *
     * @throws RuntimeException
     */
    public function getClosedDocumentIds(
        ?string $dateStart = null,
        ?string $dateEnd = null
    ): array {
        $response = Http::acceptJson()
            ->withToken(request()->bearerToken())
            // ->connectTimeout(3)
            ->timeout(10)
            ->get(
                $this->baseUrl . '/workflow-closed-documents',
                [
                    'date_closed_start' => $dateStart,
                    'date_closed_end' => $dateEnd,
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Impossible de récupérer les documents clôturés. '
                . 'Statut HTTP : ' . $response->status()
            );
        }

        return $this->extractDocumentIds($response);
    }

    /**
     * Récupère les IDs des documents correspondant à un délai d'attente.
     *
     * @param string $delay
     * @return array
     *
     * @throws RuntimeException
     */
    public function getDocumentIdsByDelay(string $delay): array
    {
        $response = Http::acceptJson()
            ->withToken(request()->bearerToken())
            ->connectTimeout(3)
            ->timeout(10)
            ->get(
                $this->baseUrl . '/workflow-delay-documents',
                [
                    'delay' => $delay,
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Impossible de récupérer les documents selon le délai. '
                . 'Statut HTTP : ' . $response->status()
            );
        }

        return $this->extractDocumentIds($response);
    }

    /**
     * Extrait et normalise les IDs depuis la réponse du Workflow.
     *
     * Format attendu :
     * {
     *     "success": true,
     *     "data": [12, 15, 21]
     * }
     *
     * @param Response $response
     * @return array
     *
     * @throws RuntimeException
     */
    protected function extractDocumentIds(Response $response): array
    {
        $documentIds = $response->json('data');

        if (!is_array($documentIds)) {
            throw new RuntimeException(
                'Réponse invalide du Workflow : la propriété data '
                . 'doit être un tableau d\'IDs.'
            );
        }

        $documentIds = array_filter(
            $documentIds,
            function ($id) {
                return is_numeric($id) && (int) $id > 0;
            }
        );

        return array_values(
            array_unique(
                array_map('intval', $documentIds)
            )
        );
    }
}