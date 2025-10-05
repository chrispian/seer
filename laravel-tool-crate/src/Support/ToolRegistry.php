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
                $out[$cls::name()] = [
                    'name'        => $cls::name(),
                    'title'       => $cls::title(),
                    'description' => $cls::shortDescription(),
                    'hint'        => sprintf("Use help.tool { name: '%s' }", $cls::name()),
                    'schema'      => $cls::schemaSummary(),
                ];
            }
        }
        return $out;
    }
}
