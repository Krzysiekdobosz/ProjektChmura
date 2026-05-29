<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nowe',
            self::InProgress => 'W trakcie',
            self::Closed => 'Zamknięte',
        };
    }
}
