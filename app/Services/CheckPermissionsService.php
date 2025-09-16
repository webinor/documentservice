<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CheckPermissionsService
{
    protected string $userServiceBaseUrl;

    public function __construct()
    {
        // À mettre dans .env par exemple USER_SERVICE_BASE_URL
        $this->userServiceBaseUrl = config('services.user_service.base_url');
    }

    /**
     * Vérifie les permissions d'un utilisateur pour une liste de documents
     *
     * @param int $userId
     * @param array $documents // Ex: [['id' => 1, 'type' => 'facture_fournisseur'], ...]
     * @return array // Format: [documentId => ['create' => true, 'view' => false, ...]]
     */
    public function checkPermissionsForUserAndDocumentTypes(int $userId, array $documents): array
    {
        $actions = ['create', 'view', 'validate', 'delete', 'reject', 'forward'];

        $payload = [
            'userId' => $userId,
            'documents' => $documents,
            'actions' => $actions,
        ];

        //return [$this->userServiceBaseUrl . '/permissions/check-batch'];
        $response = Http::acceptJson()->post($this->userServiceBaseUrl . '/permissions/check-batch', $payload);

        if ($response->failed()) {
            throw new \Exception('Erreur lors de la vérification des permissions : ' . $response->body());
        }

        $permissionsArray = $response->json(); // Tableau d'objets venant du user-service

        // Transformer en mapping documentId => permissions
        $result = [];
        foreach ($permissionsArray as $perm) {
            $documentId = $perm['documentId'];
            $result[$documentId] = $perm['permissions'] ?? [];
        }

        return $result;
    }

    public function checkPermissionsForRoleAndDocumentTypes(int $roleId, array $documents): array
    {
        $actions = ['create', 'view', 'validate', 'delete', 'reject', 'forward'];

        $payload = [
            'roleId' => $roleId,
            'documents' => $documents,
            'actions' => $actions,
        ];

        //return [$this->userServiceBaseUrl . '/permissions/check-batch'];
        $response = Http::acceptJson()->post($this->userServiceBaseUrl . '/permissions/check-batch-role', $payload);

        if ($response->failed()) {
            throw new \Exception('Erreur lors de la vérification des permissions pour ce role : ' . $response->body());
        }

        $permissionsArray = $response->json(); // Tableau d'objets venant du user-service

        // Transformer en mapping documentId => permissions
        $result = [];
        foreach ($permissionsArray as $perm) {
            $documentId = $perm['documentId'];
            $result[$documentId] = $perm['permissions'] ?? [];
        }

        return $result;
    }
}
