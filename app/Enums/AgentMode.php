<?php

namespace App\Enums;

enum AgentMode: string
{
    case Implementation = 'implementation';
    case Planning = 'planning';
    case Review = 'review';
    case Coordination = 'coordination';
    case Analysis = 'analysis';
    case Enablement = 'enablement';

    public function label(): string
    {
        return match ($this) {
            self::Implementation => 'Implementation',
            self::Planning => 'Planning',
            self::Review => 'Review',
            self::Coordination => 'Coordination',
            self::Analysis => 'Analysis',
            self::Enablement => 'Enablement',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Implementation => 'Hands-on execution and delivery work.',
            self::Planning => 'Upfront planning, scoping, and sequencing tasks.',
            self::Review => 'Code and deliverable review with quality assurance.',
            self::Coordination => 'Task orchestration, communication, and delegation.',
            self::Analysis => 'Investigation, testing, and diagnostic workflows.',
            self::Enablement => 'Documentation, education, and developer enablement.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $mode) => $mode->value, self::cases());
    }
}
