<?php

namespace App\Enums;

enum ExperienceLevel: string
{
    case Junior = 'junior';
    case Confirmed = 'confirmed';
    case Senior = 'senior';
    case Expert = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Junior => 'Junior',
            self::Confirmed => 'Confirmed',
            self::Senior => 'Senior',
            self::Expert => 'Expert',
        };
    }
}
