<?php

namespace App\Enums;

class ProviderType
{
    public const IT_SUPPLIER = 'IT_SUPPLIER';
    public const MEDICAL_SUPPLIER = 'MEDICAL_SUPPLIER';
    public const IT_PROVIDER = 'IT_PROVIDER';
    public const MEDICAL_PROVIDER = 'MEDICAL_PROVIDER';

    public static function values(): array
    {
        return [
            self::IT_SUPPLIER,
            self::MEDICAL_SUPPLIER,
            self::IT_PROVIDER,
            self::MEDICAL_PROVIDER,
        ];
    }
}