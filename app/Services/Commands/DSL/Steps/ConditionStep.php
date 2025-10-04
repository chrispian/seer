<?php

namespace App\Services\Commands\DSL\Steps;

use App\Services\Commands\DSL\TemplateEngine;

class ConditionStep extends Step
{
    public function __construct(
        protected TemplateEngine $templateEngine
    ) {}

    public function getType(): string
    {
        return 'condition';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $condition = $config['condition'] ?? '';
        $thenSteps = $config['then'] ?? [];
        $elseSteps = $config['else'] ?? [];

        if (empty($condition)) {
            throw new \InvalidArgumentException('Condition step requires a condition');
        }

        // Render the condition template first
        $renderedCondition = $this->templateEngine->render($condition, $context);
        
        if (empty($renderedCondition)) {
            throw new \InvalidArgumentException('Rendered condition is empty: ' . $condition);
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'condition' => $condition,
                'would_evaluate' => true,
                'then_steps_count' => count($thenSteps),
                'else_steps_count' => count($elseSteps),
            ];
        }

        // Evaluate condition using the rendered condition
        $conditionResult = $this->evaluateCondition($renderedCondition, $context);

        $result = [
            'condition' => $condition,
            'rendered_condition' => $renderedCondition,
            'condition_result' => $conditionResult,
            'executed_branch' => $conditionResult ? 'then' : 'else',
            'steps_executed' => [],
        ];

        // Execute appropriate branch
        $stepsToExecute = $conditionResult ? $thenSteps : $elseSteps;

        if (!empty($stepsToExecute)) {
            foreach ($stepsToExecute as $stepConfig) {
                $stepResult = $this->executeSubStep($stepConfig, $context, $dryRun);
                $result['steps_executed'][] = $stepResult;

                // Update context with step output if it has an id
                if (isset($stepConfig['id']) && isset($stepResult['output'])) {
                    $context['steps'][$stepConfig['id']] = [
                        'output' => $stepResult['output'],
                    ];
                }
            }
        }

        return $result;
    }

    protected function evaluateCondition(string $condition, array $context): bool
    {
        // Render the condition template first
        $renderedCondition = $this->templateEngine->render($condition, $context);

        // Simple condition evaluation
        // For now, we'll handle basic cases. This can be enhanced with a proper expression parser
        
        // Handle empty/null checks
        if (preg_match('/(.+?)\s*\|\s*length\s*([><=]+)\s*(\d+)/', $renderedCondition, $matches)) {
            $value = trim($matches[1]);
            $operator = $matches[2];
            $expected = (int) $matches[3];
            $actualLength = strlen(trim($value));

            return match ($operator) {
                '>' => $actualLength > $expected,
                '<' => $actualLength < $expected,
                '>=' => $actualLength >= $expected,
                '<=' => $actualLength <= $expected,
                '==' => $actualLength == $expected,
                '!=' => $actualLength != $expected,
                default => false,
            };
        }

        // Handle direct value comparisons
        if (preg_match('/(.+?)\s*([><=!]+)\s*(.+)/', $renderedCondition, $matches)) {
            $left = trim($matches[1], '"\'');
            $operator = $matches[2];
            $right = trim($matches[3], '"\'');

            // Convert numeric strings to numbers for comparison
            if (is_numeric($left)) $left = is_float($left) ? (float) $left : (int) $left;
            if (is_numeric($right)) $right = is_float($right) ? (float) $right : (int) $right;

            return match ($operator) {
                '==' => $left == $right,
                '!=' => $left != $right,
                '>' => $left > $right,
                '<' => $left < $right,
                '>=' => $left >= $right,
                '<=' => $left <= $right,
                default => false,
            };
        }

        // Handle boolean values and truthiness
        $trimmed = trim($renderedCondition);
        
        // Boolean literals
        if ($trimmed === 'true') return true;
        if ($trimmed === 'false') return false;
        
        // Empty string/null checks
        if (empty($trimmed) || $trimmed === 'null') return false;
        
        // Non-empty strings are truthy
        return !empty($trimmed);
    }

    protected function executeSubStep(array $stepConfig, array $context, bool $dryRun): array
    {
        $stepType = $stepConfig['type'] ?? 'unknown';
        $stepId = $stepConfig['id'] ?? 'step-' . uniqid();

        // Get step factory from container
        $stepFactory = app(\App\Services\Commands\DSL\Steps\StepFactory::class);

        $stepResult = [
            'id' => $stepId,
            'type' => $stepType,
            'success' => false,
            'output' => null,
            'error' => null,
        ];

        try {
            // Create and execute the step
            $step = $stepFactory->create($stepType);
            
            // Render step configuration with context
            $renderedConfig = $this->renderStepConfig($stepConfig, $context);
            
            $stepResult['output'] = $step->execute($renderedConfig, $context, $dryRun);
            $stepResult['success'] = true;

        } catch (\Exception $e) {
            $stepResult['error'] = $e->getMessage();
        }

        return $stepResult;
    }

    protected function renderStepConfig(array $stepConfig, array $context): array
    {
        $rendered = [];

        foreach ($stepConfig as $key => $value) {
            if (is_string($value)) {
                $rendered[$key] = $this->templateEngine->render($value, $context);
            } elseif (is_array($value)) {
                $rendered[$key] = $this->renderStepConfig($value, $context);
            } else {
                $rendered[$key] = $value;
            }
        }

        return $rendered;
    }

    public function validate(array $config): bool
    {
        return isset($config['condition']) && 
               (isset($config['then']) || isset($config['else']));
    }
}