<?php

namespace App\Services\Types;

use Illuminate\Support\Str;
use InvalidArgumentException;

class GeneratedTypeLocator
{
    public function resolveClasses(string $alias, bool $withRequests = false): array
    {
        $class = Str::studly($alias);
        $model = "App\\Models\\{$class}";
        $resource = "App\\Http\\Resources\\{$class}Resource";
        $createReq = "App\\Http\\Requests\\{$class}CreateRequest";
        $updateReq = "App\\Http\\Requests\\{$class}UpdateRequest";

        foreach ([$model, $resource] as $fqcn) {
            if (!class_exists($fqcn)) {
                throw new InvalidArgumentException("Generated class not found: {$fqcn}. Run types:codegen {$alias} first.");
            }
        }

        if ($withRequests) {
            foreach ([$createReq, $updateReq] as $fqcn) {
                if (!class_exists($fqcn)) {
                    throw new InvalidArgumentException("Missing request class: {$fqcn}. Regenerate with --force if renamed.");
                }
            }
            return [$model, $resource, $createReq, $updateReq];
        }

        return [$model, $resource];
    }
}
