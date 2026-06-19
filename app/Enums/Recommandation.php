<?php

namespace App\Enums;

enum Recommandation: string
{
    case Shortlisted = 'shortlisted';
    case OnHold = 'on_hold';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Shortlisted => 'Shortlisted',
            self::OnHold => 'On Hold',
            self::Rejected => 'Rejected',
        };
    }
}
