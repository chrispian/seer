<?php

namespace App\Tools\Contracts;

interface SummarizesTool
{
    public static function summaryName(): string;

    public static function summaryTitle(): string;

    public static function summaryDescription(): string;

    /** @return array<string,string> */
    public static function schemaSummary(): array;
}
