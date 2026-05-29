<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Oczekuje',
            self::Paid => 'Opłacone',
            self::Failed => 'Nieudane',
            self::Cancelled => 'Anulowane',
        };
    }
}
