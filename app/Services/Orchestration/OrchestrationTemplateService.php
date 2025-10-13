<?php

namespace App\Services\Orchestration;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OrchestrationTemplateService
{
    private string $templatesPath;

    public function __construct()
    {
        $this->templatesPath = base_path('delegation/.templates');
    }

    public function getAvailableTemplates(): array
    {
        return [
            'sprints' => $this->getSprintTemplates(),
            'tasks' => $this->getTaskTemplates(),
            'agents' => $this->getAgentTemplates(),
        ];
    }

    public function loadTemplate(string $type, string $name): ?string
    {
        $path = match($type) {
            'sprint' => "{$this->templatesPath}/sprint-template/{$name}",
            'task' => "{$this->templatesPath}/task-template/{$name}",
            'agent' => "{$this->templatesPath}/agent-base/{$name}",
            default => null,
        };

        if (!$path || !File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    public function parseTemplate(string $content, array $variables): string
    {
        $parsed = $content;

        foreach ($variables as $key => $value) {
            $placeholder = "{{" . $key . "}}";
            
            if (is_array($value)) {
                $value = $this->formatArrayValue($value);
            }
            
            $parsed = str_replace($placeholder, $value, $parsed);
        }

        $parsed = $this->handleConditionals($parsed, $variables);

        return $parsed;
    }

    public function validateTemplate(string $content): array
    {
        $errors = [];

        $requiredPlaceholders = ['{{sprint_code}}', '{{title}}'];
        foreach ($requiredPlaceholders as $placeholder) {
            if (!str_contains($content, $placeholder)) {
                $errors[] = "Missing required placeholder: {$placeholder}";
            }
        }

        return $errors;
    }

    private function getSprintTemplates(): array
    {
        $path = "{$this->templatesPath}/sprint-template";
        
        if (!File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->map(fn($file) => [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'type' => $file->getExtension(),
            ])
            ->values()
            ->toArray();
    }

    private function getTaskTemplates(): array
    {
        $path = "{$this->templatesPath}/task-template";
        
        if (!File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->map(fn($file) => [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'type' => $file->getExtension(),
            ])
            ->values()
            ->toArray();
    }

    private function getAgentTemplates(): array
    {
        $path = "{$this->templatesPath}/agent-base";
        
        if (!File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->map(fn($file) => [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'type' => $file->getExtension(),
            ])
            ->values()
            ->toArray();
    }

    private function formatArrayValue(array $value): string
    {
        if (empty($value)) {
            return '';
        }

        if (array_is_list($value)) {
            return implode("\n- ", array_merge([''], $value));
        }

        $formatted = '';
        foreach ($value as $key => $val) {
            $formatted .= "\n- **{$key}**: {$val}";
        }

        return $formatted;
    }

    private function handleConditionals(string $content, array $variables): string
    {
        $pattern = '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s';
        
        return preg_replace_callback($pattern, function($matches) use ($variables) {
            $variable = $matches[1];
            $conditionalContent = $matches[2];
            
            if (isset($variables[$variable]) && $variables[$variable]) {
                return $conditionalContent;
            }
            
            return '';
        }, $content);
    }
}
