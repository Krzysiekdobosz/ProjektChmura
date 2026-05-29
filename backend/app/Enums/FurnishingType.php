<?php

namespace App\Enums;

enum FurnishingType: string
{
    case Furnished = 'furnished';
    case PartiallyFurnished = 'partially_furnished';
    case Unfurnished = 'unfurnished';

    public function label(): string
    {
        return match ($this) {
            self::Furnished => 'Umeblowane',
            self::PartiallyFurnished => 'Częściowo umeblowane',
            self::Unfurnished => 'Nieumeblowane',
        };
    }
}
