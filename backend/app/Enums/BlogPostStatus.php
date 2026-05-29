<?php

namespace App\Enums;

enum BlogPostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Szkic',
            self::Published => 'Opublikowany',
        };
    }
}
