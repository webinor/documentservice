<?php

namespace App\Services;

use App\Models\Misc\Document;

class DocumentUrlResolver
{
    /**
     * Détermine le chemin frontend d'un document en fonction :
     * - de l'identité de l'utilisateur connecté par rapport à l'acteur ;
     * - des permissions effectives de l'utilisateur ;
     * - des chemins configurés pour le type de document.
     *
     * Règles :
     * 1. Si l'utilisateur est l'acteur du document : own.
     * 2. Sinon, s'il possède une permission de traitement : validate.
     * 3. Sinon : own.
     *
     * @param Document $document
     * @param mixed|null $user
     * @return string|null
     */
    public function resolve(
        Document $document,
        $user = null
    ): ?string {
        $document->loadMissing('document_type');

        $documentType = $document->document_type;

        if (!$documentType) {
            return null;
        }

        $documentUuid = $document->uuid ?? null;

        if (!$documentUuid) {
            return null;
        }

        $documentTypeId = (int) $documentType->id;

        if ($documentTypeId <= 0) {
            return null;
        }

        // Utiliser l'utilisateur fourni ou celui du middleware.
        if ($user === null) {
            $user = request()->get('user');
        }

        // Déterminer si l'utilisateur connecté est l'acteur.
        $isActor = $this->isDocumentActor($document, $user);

        /*
         * L'acteur doit toujours accéder au document
         * par son espace personnel, même s'il possède
         * des permissions de traitement.
         */
        if ($isActor) {
            $basePath = $this->getConfiguredPath(
                $documentType,
                ['view_own_route']
            );
        } else {
            // Pour les autres utilisateurs, vérifier les permissions.
            $permissions = $this->extractPermissions($user);

            $canProcess = $this->hasProcessingPermission(
                $permissions,
                $documentTypeId
            );

            if ($canProcess) {
                $basePath = $this->getConfiguredPath(
                    $documentType,
                    ['view_route']
                );
            } else {
                $basePath = $this->getConfiguredPath(
                    $documentType,
                    ['view_own_route']
                );
            }
        }

        /*
         * Ne pas produire une URL incorrecte si le chemin
         * nécessaire n'est pas configuré.
         */
        if (!$basePath) {
            return null;
        }

        return '/' . trim($basePath, '/')
            . '/'
            . rawurlencode((string) $documentUuid);
    }

    /**
 * Vérifie si l'utilisateur connecté est l'acteur du document.
 *
 * Document.actor_id représente l'identifiant de l'employé.
 *
 * @param Document $document
 * @param mixed $user
 * @return bool
 */
protected function isDocumentActor(
    Document $document,
    $user
): bool {
    if (is_string($user)) {
        $decoded = json_decode($user, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $user = $decoded;
    }

    if (is_object($user)) {
        $user = json_decode(
            json_encode($user),
            true
        );
    }

    if (!is_array($user)) {
        return false;
    }

    // Certaines réponses encapsulent les données dans "message".
    if (
        isset($user['message'])
        && is_string($user['message'])
    ) {
        $decodedMessage = json_decode(
            $user['message'],
            true
        );

        if (
            json_last_error() === JSON_ERROR_NONE
            && is_array($decodedMessage)
        ) {
            $user = $decodedMessage;
        }
    }

    $actorId = $document->actor_id;

    if ($actorId === null) {
        return false;
    }

    /*
     * Récupérer l'identifiant de l'employé.
     *
     * Adapter ces clés à la structure réelle de l'objet
     * utilisateur transmis par ton middleware.
     */
    $employeeId = $user['employeeContext']['employee_id']
        ?? $user['employeeContext']['id']
        ?? $user['employee_id']
        ?? $user['employee']['id']
        ?? null;

    if ($employeeId === null) {
        return false;
    }

    return (string) $actorId === (string) $employeeId;
}

    /**
     * Détermine si l'utilisateur peut traiter ce type
     * de document dans le workflow.
     *
     * @param array $permissions
     * @param int $documentTypeId
     * @return bool
     */
    protected function hasProcessingPermission(
        array $permissions,
        int $documentTypeId
    ): bool {
        $actions = [
            'validate',
            'sign',
            'checkout',
        ];

        foreach ($actions as $action) {
            $permission = sprintf(
                'doctype_%d_%s',
                $documentTypeId,
                $action
            );

            if (in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extrait les permissions effectives depuis l'utilisateur.
     *
     * Accepte un tableau, un objet ou une chaîne JSON.
     *
     * @param mixed $user
     * @return array
     */
    protected function extractPermissions($user): array
    {
        if (is_string($user)) {
            $decoded = json_decode($user, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $user = $decoded;
            } else {
                return [];
            }
        }

        if (is_object($user)) {
            $user = json_decode(
                json_encode($user),
                true
            );
        }

        if (!is_array($user)) {
            return [];
        }

        /*
         * Certains services peuvent encapsuler l'utilisateur
         * dans un champ message contenant du JSON.
         */
        if (
            isset($user['message'])
            && is_string($user['message'])
        ) {
            $decodedMessage = json_decode(
                $user['message'],
                true
            );

            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($decodedMessage)
            ) {
                $user = $decodedMessage;
            }
        }

        $permissions = $user['effective_permissions'] ?? [];

        if (!is_array($permissions)) {
            $permissions = [];
        }

        /*
         * Compatibilité avec les réponses qui exposent aussi
         * les permissions dans roles[].permissions.
         */
        foreach (($user['roles'] ?? []) as $role) {
            foreach (($role['permissions'] ?? []) as $permission) {
                if (!empty($permission['name'])) {
                    $permissions[] = $permission['name'];
                }
            }
        }

        return array_values(array_unique(array_filter(
            $permissions,
            function ($permission) {
                return is_string($permission)
                    && $permission !== '';
            }
        )));
    }

    /**
     * Récupère le premier chemin non vide parmi les attributs
     * disponibles sur le type de document.
     *
     * @param mixed $documentType
     * @param array $attributeNames
     * @return string|null
     */
    protected function getConfiguredPath(
        $documentType,
        array $attributeNames
    ): ?string {
        foreach ($attributeNames as $attributeName) {
            $value = $documentType->getAttribute(
                $attributeName
            );

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}