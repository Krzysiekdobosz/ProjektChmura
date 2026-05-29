<?php

namespace App\Enums;

enum ViewingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Oczekuje',
            self::Accepted => 'Zaakceptowane',
            self::Rejected => 'Odrzucone',
            self::Cancelled => 'Anulowane',
            self::Completed => 'Zakończone',
        };
    }
}
