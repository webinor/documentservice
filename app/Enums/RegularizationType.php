<?php

namespace App\Enums;

class RegularizationType
{
    const INTERNAL = 'INTERNAL';
    const ASSISTANCE = 'ASSISTANCE';


    /**
     * Retourne toutes les valeurs autorisées.
     *
     * @return array
     */
    public static function values()
    {
        return [
            self::INTERNAL,
            self::ASSISTANCE,
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
                'value' => self::ASSISTANCE,
                'label' => 'Assistance',
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
            self::ASSISTANCE => 'Assistance',
        ];

        return $labels[$value] ?? null;
    }
}