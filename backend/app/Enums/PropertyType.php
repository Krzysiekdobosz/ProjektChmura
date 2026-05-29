<?php

namespace App\Enums;

enum PropertyType: string
{
    case Apartment = 'apartment';
    case Room = 'room';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'Mieszkanie',
            self::Room => 'Pokój',
        };
    }
}
