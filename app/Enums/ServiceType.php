<?php

namespace App\Enums;

enum ServiceType: string
{
    case BASIC = 'basic';
    case STANDARD = 'standard';
    case PREMIUM = 'premium';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
