<?php

namespace App\Enums;

enum OrchestrationPhase: string
{
    case INTAKE = 'intake';
    case RESEARCH = 'research';
    case PLAN = 'plan';
    case EXECUTE = 'execute';
    case REVIEW = 'review';
    case CLOSE = 'close';

    public function label(): string
    {
        return match ($this) {
            self::INTAKE => 'Intake',
            self::RESEARCH => 'Research',
            self::PLAN => 'Plan',
            self::EXECUTE => 'Execute',
            self::REVIEW => 'Review',
            self::CLOSE => 'Close',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::INTAKE => 1,
            self::RESEARCH => 2,
            self::PLAN => 3,
            self::EXECUTE => 4,
            self::REVIEW => 5,
            self::CLOSE => 6,
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::INTAKE => self::RESEARCH,
            self::RESEARCH => self::PLAN,
            self::PLAN => self::EXECUTE,
            self::EXECUTE => self::REVIEW,
            self::REVIEW => self::CLOSE,
            self::CLOSE => null,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        if ($target === $this) {
            return false;
        }

        return $target->order() === $this->order() + 1;
    }

    public static function values(): array
    {
        return array_map(static fn (self $phase) => $phase->value, self::cases());
    }

    public static function first(): self
    {
        return self::INTAKE;
    }
}
