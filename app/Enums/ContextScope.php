<?php

namespace App\Enums;

enum ContextScope: string
{
    case SESSION = 'session';
    case TASK = 'task';
    case SPRINT = 'sprint';
    case PROJECT = 'project';

    public function label(): string
    {
        return match ($this) {
            self::SESSION => 'Session',
            self::TASK => 'Task',
            self::SPRINT => 'Sprint',
            self::PROJECT => 'Project',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $scope) => $scope->value, self::cases());
    }
}
