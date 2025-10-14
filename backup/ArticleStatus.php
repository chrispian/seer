<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Review => 'Under Review',
            self::Published => 'Published',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Review => 'yellow',
            self::Published => 'green',
        };
    }
}
