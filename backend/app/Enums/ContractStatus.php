<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Signed = 'signed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Szkic',
            self::Generated => 'Wygenerowana',
            self::Signed => 'Podpisana',
        };
    }
}
