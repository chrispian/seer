<?php

namespace App\Services\Commands\DSL;

class AiGenerateStep implements Step
{
    public function execute(array $def, array $scope)
    {
        $prompt = $def['prompt'] ?? '';
        if (is_file(($p = data_get($def, 'prompt')) ?? '')) {
            $prompt = file_get_contents($p);
        }
        $rendered = Templating::render($prompt, $scope);

        // TODO: inject your LLM client here and return its output.
        // For now, just echo the prompt back.
        $expect = $def['expect'] ?? 'text';
        return $expect === 'json' ? ['_demo' => 'replace with LLM JSON'] : $rendered;
    }
}
