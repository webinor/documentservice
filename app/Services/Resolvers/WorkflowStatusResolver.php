<?php

namespace App\Services\Resolvers;

class WorkflowStatusResolver
{
    /**
     * Correspondance entre les statuts techniques
     * du workflow et leurs libellés français.
     *
     * @var array
     */
    protected static $labels = [
        'PENDING' => 'En attente',
        'IN_PROGRESS' => 'En cours',
        'COMPLETE' => 'Terminé',
        'REJECTED' => 'Rejeté',
        'CANCELLED' => 'Annulé',
        'SUSPENDED' => 'Suspendu',
    ];

    /**
     * Traduit un statut du workflow en français.
     *
     * @param string|null $status
     * @return string|null
     */
    public static function resolve(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return self::$labels[$status] ?? $status;
    }
}