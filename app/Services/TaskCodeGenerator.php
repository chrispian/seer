<?php

namespace App\Services;

use App\Models\WorkItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaskCodeGenerator
{
    /**
     * Generate a unique task code based on task name/description
     * Pattern: PREFIX-### (e.g., ENG-001, UX-042, DOC-015)
     */
    public function generate(string $taskName, ?string $description = null): string
    {
        $maxAttempts = 5;
        $attempts = 0;

        do {
            $code = $this->generateCode($taskName, $description, $attempts);
            $attempts++;

            if ($attempts >= $maxAttempts) {
                // Fallback to deterministic generation
                return $this->generateFallback($taskName);
            }
        } while ($this->codeExists($code));

        return $code;
    }

    /**
     * Generate code using LLM to extract meaningful prefix from task context
     */
    private function generateCode(string $taskName, ?string $description, int $attempt): string
    {
        try {
            // Use local LLaMA to generate prefix
            $prefix = $this->generatePrefix($taskName, $description, $attempt);
            
            // Get next number for this prefix
            $number = $this->getNextNumber($prefix);
            
            return sprintf('%s-%03d', $prefix, $number);
            
        } catch (\Exception $e) {
            Log::warning('Task code generation failed, using fallback', [
                'task_name' => $taskName,
                'error' => $e->getMessage(),
            ]);
            return $this->generateFallback($taskName);
        }
    }

    /**
     * Use LLM to generate a meaningful 2-4 letter prefix from task context
     */
    private function generatePrefix(string $taskName, ?string $description, int $attempt): string
    {
        $context = $taskName;
        if ($description) {
            $context .= "\n" . $description;
        }

        // Get examples from existing tasks
        $examples = $this->getExampleCodes();

        $prompt = <<<PROMPT
You are a task code generator. Generate a 2-4 letter prefix code for this task.

Guidelines:
- Use 2-4 uppercase letters
- Extract from key words in the task name
- Common prefixes: ENG (engineering), UX (user experience), DOC (documentation), API, DB, UI, TEST, FIX, FEAT
- Be consistent with existing patterns
- Return ONLY the prefix, nothing else

Existing examples for pattern reference:
{$examples}

Task to code:
{$context}

Generate prefix (2-4 letters only):
PROMPT;

        $response = Http::timeout(5)->post(config('openai.base_url') . '/v1/chat/completions', [
            'model' => config('openai.model'),
            'messages' => [
                ['role' => 'system', 'content' => 'You generate concise task code prefixes. Respond with ONLY the prefix letters.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3 + ($attempt * 0.2), // Increase variation on retries
            'max_tokens' => 10,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('LLM request failed');
        }

        $prefix = trim($response->json('choices.0.message.content', ''));
        
        // Sanitize: extract only letters, uppercase, limit to 4 chars
        $prefix = strtoupper(preg_replace('/[^A-Z]/i', '', $prefix));
        $prefix = substr($prefix, 0, 4);
        
        if (strlen($prefix) < 2) {
            throw new \RuntimeException('Generated prefix too short');
        }

        return $prefix;
    }

    /**
     * Get examples of existing task codes for context
     */
    private function getExampleCodes(): string
    {
        $tasks = WorkItem::orderByDesc('created_at')
            ->limit(10)
            ->get();

        $examples = [];
        foreach ($tasks as $task) {
            $code = Arr::get($task->metadata, 'task_code');
            $name = Arr::get($task->metadata, 'task_name', 'Unknown');
            if ($code) {
                $examples[] = "{$code}: {$name}";
            }
        }

        return implode("\n", $examples) ?: "No examples available";
    }

    /**
     * Get the next sequential number for a given prefix
     */
    private function getNextNumber(string $prefix): int
    {
        // Find all existing codes with this prefix
        $tasks = WorkItem::whereNotNull('metadata->task_code')->get();
        
        $maxNumber = 0;
        foreach ($tasks as $task) {
            $code = Arr::get($task->metadata, 'task_code');
            if ($code && str_starts_with($code, $prefix . '-')) {
                // Extract number from CODE-###
                if (preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/', $code, $matches)) {
                    $number = (int) $matches[1];
                    $maxNumber = max($maxNumber, $number);
                }
            }
        }

        return $maxNumber + 1;
    }

    /**
     * Check if a task code already exists
     */
    private function codeExists(string $code): bool
    {
        return WorkItem::where('metadata->task_code', $code)->exists();
    }

    /**
     * Fallback: Deterministic generation from task name
     * Extracts capital letters or first letters of words
     */
    private function generateFallback(string $taskName): string
    {
        // Try to extract capital letters first
        preg_match_all('/[A-Z]/', $taskName, $matches);
        $capitals = implode('', $matches[0]);
        
        if (strlen($capitals) >= 2) {
            $prefix = substr($capitals, 0, 4);
        } else {
            // Extract first letter of each word
            $words = preg_split('/[\s\-_]+/', $taskName);
            $prefix = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $prefix .= strtoupper($word[0]);
                }
                if (strlen($prefix) >= 4) break;
            }
        }

        // Ensure we have at least 2 characters
        if (strlen($prefix) < 2) {
            $prefix = 'TASK';
        }

        $number = $this->getNextNumber($prefix);
        return sprintf('%s-%03d', $prefix, $number);
    }
}
