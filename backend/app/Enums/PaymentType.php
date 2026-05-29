<?php

namespace App\Enums;

enum PaymentType: string
{
    case Rent = 'rent';
    case Deposit = 'deposit';
    case ExtraFee = 'extra_fee';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Czynsz',
            self::Deposit => 'Kaucja',
            self::ExtraFee => 'Opłata dodatkowa',
        };
    }
}
