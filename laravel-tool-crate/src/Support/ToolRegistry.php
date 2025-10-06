<?php

namespace HollisLabs\ToolCrate\Support;

use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;

class ToolRegistry
{
    public static function summarize(array $toolClasses): array
    {
        $out = [];
        foreach ($toolClasses as $cls) {
            if (is_subclass_of($cls, SummarizesTool::class)) {
                /** @var SummarizesTool $cls */
                $name = $cls::summaryName();

                $out[$name] = [
                    'name'        => $name,
                    'title'       => $cls::summaryTitle(),
                    'description' => $cls::summaryDescription(),
                    'hint'        => sprintf("Use help.tool { name: '%s' }", $name),
                    'schema'      => $cls::schemaSummary(),
                ];
            }
        }
        return $out;
    }
}
