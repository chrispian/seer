<?php

namespace HollisLabs\ToolCrate\Tools\Contracts;

interface SummarizesTool
{
    public static function name(): string;
    public static function title(): string;
    public static function shortDescription(): string;
    /** @return array<string,string> */
    public static function schemaSummary(): array;
}
