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
/**
 * Récupère en une seule requête les instances de workflow
 * associées à plusieurs documents.
 *
 * Le token utilisé dépend du contexte d'appel :
 *
 * 1. Si un token Bearer utilisateur est disponible, il est privilégié.
 * 2. Sinon, le token de service configuré est utilisé.
 *
 * Le token est transmis dans l'en-tête Authorization.
 *
 * Si le Workflow Service attend spécifiquement X-SERVICE-TOKEN
 * pour les appels interservices, utiliser cet en-tête à la place
 * du Bearer pour le token de service.
 *
 * @param array $documentIds
 * @return array
 *
 * @throws RuntimeException
 */
public function getInstancesByDocumentIds(array $documentIds): array
{
    /*
    |--------------------------------------------------------------------------
    | 1. Normalisation des identifiants
    |--------------------------------------------------------------------------
    |
    | On convertit les identifiants en entiers, on supprime les doublons
    | et on élimine les valeurs invalides.
    |
    */

    $documentIds = array_values(array_unique(array_filter(
        array_map('intval', $documentIds),
        function ($id) {
            return $id > 0;
        }
    )));

    /*
     * Aucun appel HTTP n'est nécessaire sans identifiant valide.
     */

    if (empty($documentIds)) {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Sélection du token
    |--------------------------------------------------------------------------
    |
    | On privilégie le token utilisateur de la requête courante.
    |
    | Si aucun token utilisateur n'est disponible, on utilise le token
    | interservices défini dans la configuration Laravel.
    |
    */

    $userToken = request()->bearerToken();

    $serviceToken = config('services.workflow_service.token');

    $token = $userToken ?: $serviceToken;

    /*
     * On vérifie qu'un token est disponible avant l'appel HTTP.
     */

    if (empty($token)) {
        throw new RuntimeException(
            'Impossible d’appeler le Workflow Service : '
            . 'aucun token utilisateur ou token de service n’est configuré.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Préparation des en-têtes HTTP
    |--------------------------------------------------------------------------
    |
    | Si le token utilisateur est utilisé, on transmet Authorization: Bearer.
    |
    | Si le token de service est utilisé, on transmet X-SERVICE-TOKEN,
    | conformément au mécanisme d'authentification interservices.
    |
    */

    $httpClient = Http::acceptJson()
        ->timeout(20);

    if (!empty($userToken)) {
        $httpClient = $httpClient->withToken($userToken);
    } else {
        $httpClient = $httpClient->withHeaders([
            'X-SERVICE-TOKEN' => $serviceToken,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Appel du Workflow Service
    |--------------------------------------------------------------------------
    |
    | Les identifiants sont envoyés en une seule requête POST.
    |
    */

    $response = $httpClient->post(
        $this->baseUrl . '/workflow-instances/by-document-ids',
        [
            'document_ids' => $documentIds,
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | 5. Gestion des erreurs HTTP
    |--------------------------------------------------------------------------
    |
    | On ne journalise jamais le token dans les exceptions ou les logs.
    |
    */

    if ($response->failed()) {
        throw new RuntimeException(
            'Erreur lors de la récupération des instances du '
            . 'Workflow Service. HTTP '
            . $response->status()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Validation de la réponse
    |--------------------------------------------------------------------------
    |
    | Le service doit retourner une réponse contenant un tableau data.
    |
    */

    $data = $response->json('data');

    if (!is_array($data)) {
        throw new RuntimeException(
            'Réponse invalide du Workflow Service : '
            . 'le champ data doit être un tableau.'
        );
    }

    return $data;
}
}