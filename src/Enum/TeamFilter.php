<?php

namespace App\Enum;

enum TeamFilter: string
{
    case All = 'all';
    case Member = 'member';
    case Leader = 'leader';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Toutes',
            self::Member => 'Membre',
            self::Leader => 'Chef',
        };
    }
}
