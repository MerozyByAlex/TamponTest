<?php

namespace App\Enum;

enum ProductConditionEnum: string
{
    case NEW = 'new';
    case USED = 'used';
    case REFURBISHED = 'refurbished';

    // Optionnel : une méthode pour obtenir un libellé plus convivial si nécessaire
    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::USED => 'Used',
            self::REFURBISHED => 'Refurbished',
        };
    }
}