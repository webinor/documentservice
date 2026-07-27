<?php

namespace App\Enums;

class AssistanceMode
{
    const INTERNAL = 'INTERNAL';

    const ASSISTANCE_NORMAL = 'ASSISTANCE_NORMAL';

    const ASSISTANCE_URGENT = 'ASSISTANCE_URGENT';

    /**
     * Retourne toutes les valeurs autorisées.
     *
     * @return array
     */
    public static function values()
    {
        return [
            self::INTERNAL,
            self::ASSISTANCE_NORMAL,
            self::ASSISTANCE_URGENT,
        ];
    }

    /**
     * Retourne les options pour les formulaires.
     *
     * @return array
     */
    public static function options()
    {
        return [
            [
                'value' => self::INTERNAL,
                'label' => 'Fonctionnement interne',
            ],
            [
                'value' => self::ASSISTANCE_NORMAL,
                'label' => 'Assistance',
            ],
            [
                'value' => self::ASSISTANCE_URGENT,
                'label' => 'Assistance urgente',
            ],
        ];
    }

    /**
     * Retourne le libellé d'une valeur.
     *
     * @param string $value
     * @return string|null
     */
    public static function label($value)
    {
        $labels = [
            self::INTERNAL => 'Fonctionnement interne',
            self::ASSISTANCE_NORMAL => 'Assistance',
            self::ASSISTANCE_URGENT => 'Assistance urgente',
        ];

        return $labels[$value] ?? null;
    }
}