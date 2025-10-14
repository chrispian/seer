<?php

namespace App\Services\Orchestration;

use App\Models\OrchestrationBug;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OrchestrationBugService
{
    public function hashBug(
        string $errorMessage, 
        ?string $filePath = null, 
        ?int $lineNumber = null,
        ?string $stackTrace = null
    ): string {
        $components = [
            $this->normalizeErrorMessage($errorMessage),
            $filePath ? $this->normalizeFilePath($filePath) : '',
            $lineNumber ?? '',
            $stackTrace ? $this->extractStackTraceSignature($stackTrace) : '',
        ];

        $hashInput = implode('|', array_filter($components));
        
        return hash('sha256', $hashInput);
    }

    public function logBug(
        string $errorMessage,
        ?string $taskCode = null,
        ?string $filePath = null,
        ?int $lineNumber = null,
        ?string $stackTrace = null,
        array $additionalContext = []
    ): OrchestrationBug {
        $bugHash = $this->hashBug($errorMessage, $filePath, $lineNumber, $stackTrace);

        $context = array_merge([
            'captured_at' => now()->toIso8601String(),
            'error_type' => $this->detectErrorType($errorMessage),
            'occurrence_count' => $this->getOccurrenceCount($bugHash) + 1,
        ], $additionalContext);

        $existing = OrchestrationBug::where('bug_hash', $bugHash)->first();
        
        if ($existing) {
            $updatedContext = $existing->context ?? [];
            $updatedContext['last_seen'] = now()->toIso8601String();
            $updatedContext['occurrence_count'] = ($updatedContext['occurrence_count'] ?? 1) + 1;
            $updatedContext['last_task_code'] = $taskCode;
            
            $existing->update(['context' => $updatedContext]);
            
            Log::info('Bug occurrence logged (duplicate)', [
                'bug_hash' => $bugHash,
                'bug_id' => $existing->id,
                'occurrences' => $updatedContext['occurrence_count'],
            ]);
            
            return $existing;
        }

        $bug = OrchestrationBug::create([
            'bug_hash' => $bugHash,
            'task_code' => $taskCode,
            'error_message' => substr($errorMessage, 0, 1000),
            'file_path' => $filePath,
            'line_number' => $lineNumber,
            'stack_trace' => $stackTrace,
            'context' => $context,
        ]);

        Log::info('Bug logged (new)', [
            'bug_hash' => $bugHash,
            'task_code' => $taskCode,
            'file_path' => $filePath,
        ]);

        return $bug;
    }

    public function searchSimilar(string $bugHash, int $limit = 10): Collection
    {
        $bug = OrchestrationBug::where('bug_hash', $bugHash)->first();
        
        if (!$bug) {
            return collect();
        }
        
        return collect([$bug]);
    }

    public function searchByErrorMessage(string $errorMessage, int $limit = 10): Collection
    {
        $normalized = $this->normalizeErrorMessage($errorMessage);
        
        return OrchestrationBug::where('error_message', 'ILIKE', "%{$normalized}%")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function isDuplicate(string $bugHash): bool
    {
        return OrchestrationBug::where('bug_hash', $bugHash)->exists();
    }

    public function getOccurrenceCount(string $bugHash): int
    {
        $bug = OrchestrationBug::where('bug_hash', $bugHash)->first();
        
        if (!$bug) {
            return 0;
        }
        
        return $bug->context['occurrence_count'] ?? 1;
    }

    public function promptUserAction(OrchestrationBug $bug, bool $isRelatedToCurrentTask): array
    {
        $occurrences = $this->getOccurrenceCount($bug->bug_hash);
        $resolved = $bug->isResolved();

        $message = "Bug detected: {$bug->error_message}\n";
        
        if ($occurrences > 1) {
            $message .= "\n⚠️  This bug has occurred {$occurrences} times before.";
        }

        if ($resolved) {
            $message .= "\n✓ Previously resolved: {$bug->resolution}";
        }

        if ($bug->file_path) {
            $message .= "\nFile: {$bug->file_path}" . ($bug->line_number ? ":{$bug->line_number}" : '');
        }

        $options = [];

        if ($isRelatedToCurrentTask) {
            $options['fix_now'] = 'Fix this bug now (blocks current task)';
            $options['log_and_continue'] = 'Log bug and continue with task';
        } else {
            $options['log_only'] = 'Log bug for later (unrelated to current task)';
            $options['fix_now'] = 'Fix this bug now';
        }

        $options['provide_context'] = 'Provide more context about this bug';

        return [
            'message' => $message,
            'options' => $options,
            'bug' => $bug,
            'is_duplicate' => $occurrences > 1,
            'is_resolved' => $resolved,
            'occurrences' => $occurrences,
        ];
    }

    protected function normalizeErrorMessage(string $message): string
    {
        $message = preg_replace('/\d+/', '#', $message);
        
        $message = preg_replace('/0x[0-9a-f]+/i', '0xHEX', $message);
        
        $message = preg_replace('/["\']([^"\']+)["\']/i', 'STRING', $message);
        
        $message = preg_replace('/\s+/', ' ', $message);
        
        return trim($message);
    }

    protected function normalizeFilePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        
        $projectRoot = base_path();
        if (str_starts_with($path, $projectRoot)) {
            $path = substr($path, strlen($projectRoot) + 1);
        }

        return $path;
    }

    protected function extractStackTraceSignature(string $stackTrace): string
    {
        $lines = explode("\n", $stackTrace);
        
        $firstLine = $lines[0] ?? '';
        
        $firstLine = preg_replace('/\d+/', '#', $firstLine);
        
        return trim($firstLine);
    }

    protected function detectErrorType(string $message): string
    {
        $patterns = [
            'syntax' => '/syntax error|parse error|unexpected/i',
            'type' => '/type error|undefined (method|property|variable)/i',
            'runtime' => '/runtime error|exception|fatal error/i',
            'database' => '/sql|database|query|connection/i',
            'network' => '/connection refused|timeout|network/i',
            'permission' => '/permission denied|access denied|forbidden/i',
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $message)) {
                return $type;
            }
        }

        return 'unknown';
    }

    public function getBugStats(?string $taskCode = null): array
    {
        $query = OrchestrationBug::query();
        
        if ($taskCode) {
            $query->where('task_code', $taskCode);
        }

        $total = $query->count();
        $resolved = $query->whereNotNull('resolved_at')->count();
        $unresolved = $total - $resolved;

        $byType = $query->get()->groupBy(function ($bug) {
            return $bug->context['error_type'] ?? 'unknown';
        })->map->count();

        return [
            'total' => $total,
            'resolved' => $resolved,
            'unresolved' => $unresolved,
            'by_type' => $byType->toArray(),
        ];
    }
}
