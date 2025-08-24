<?php

namespace App\Enums;

enum EditMode: string
{
    case Reference = 'reference';
    case Copy = 'copy';

    public function label(): string
    {
        return match($this) {
            self::Reference => 'Reference',
            self::Copy => 'Copy',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::Reference => 'Reference the original fragment (changes reflect automatically)',
            self::Copy => 'Copy the fragment content (independent snapshot)',
        };
    }
}