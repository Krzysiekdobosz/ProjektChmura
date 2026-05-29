<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Szkic',
            self::Pending => 'Oczekuje na weryfikację',
            self::Published => 'Opublikowana',
            self::Rejected => 'Odrzucona',
            self::Archived => 'Zarchiwizowana',
        };
    }
}
