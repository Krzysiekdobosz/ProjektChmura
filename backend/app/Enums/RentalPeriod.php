<?php

namespace App\Enums;

enum RentalPeriod: string
{
    case Minimum1Month = 'minimum_1_month';
    case Minimum3Months = 'minimum_3_months';
    case Minimum6Months = 'minimum_6_months';
    case Minimum12Months = 'minimum_12_months';
    case Indefinite = 'indefinite';

    public function label(): string
    {
        return match ($this) {
            self::Minimum1Month => 'Minimum 1 miesiąc',
            self::Minimum3Months => 'Minimum 3 miesiące',
            self::Minimum6Months => 'Minimum 6 miesięcy',
            self::Minimum12Months => 'Minimum 12 miesięcy',
            self::Indefinite => 'Bezterminowo',
        };
    }
}
