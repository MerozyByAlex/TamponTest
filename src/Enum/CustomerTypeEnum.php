<?php

namespace App\Enum;

enum CustomerTypeEnum: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';

    public function getLabel(): string // Optional: for display
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::COMPANY => 'Company',
        };
    }
}