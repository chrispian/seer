<?php

namespace App\Services;

use App\Models\AgentLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class AgentLogImportService
{
    private array $importedFiles = [];

    private array $stats = [
        'files_processed' => 0,
        'entries_imported' => 0,
        'entries_skipped' => 0,
        'errors' => [],
    ];

    /**
     * Import logs from specified sources
     */
    public function import(array $options = []): array
    {
        $sources = $options['sources'] ?? ['opencode', 'claude', 'codex'];
        $since = $options['since'] ?? null;
        $dryRun = $options['dry_run'] ?? false;

        foreach ($sources as $source) {
            switch ($source) {
                case 'opencode':
                    $this->importOpenCodeLogs($since, $dryRun);
                    break;
                case 'claude':
                    $this->importClaudeLogs($since, $dryRun);
                    break;
                case 'codex':
                    $this->importCodexLogs($since, $dryRun);
                    break;
            }
        }

        return $this->stats;
    }

    /**
     * Import OpenCode logs from ~/.local/share/opencode/log/
     */
    private function importOpenCodeLogs(?string $since, bool $dryRun): void
    {
        $logDir = $_SERVER['HOME'].'/.local/share/opencode/log';

        if (! File::isDirectory($logDir)) {
            $this->stats['errors'][] = "OpenCode log directory not found: {$logDir}";

            return;
        }

        $files = File::files($logDir);
        $sinceDate = $since ? Carbon::parse($since) : null;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'log') {
                continue;
            }

            $fileModTime = Carbon::createFromTimestamp($file->getMTime());

            if ($sinceDate && $fileModTime->isBefore($sinceDate)) {
                continue;
            }

            $this->processOpenCodeLogFile($file->getPathname(), $dryRun);
        }
    }

    /**
     * Import Claude logs from multiple sources
     */
    private function importClaudeLogs(?string $since, bool $dryRun): void
    {
        // Import Desktop logs (old format)
        $this->importClaudeDesktopLogs($since, $dryRun);

        // Import Projects logs (new format)
        $this->importClaudeProjectsLogs($since, $dryRun);
    }

    /**
     * Import Claude Desktop logs from ~/Library/Logs/Claude/
     */
    private function importClaudeDesktopLogs(?string $since, bool $dryRun): void
    {
        $logDir = $_SERVER['HOME'].'/Library/Logs/Claude';

        if (! File::isDirectory($logDir)) {
            // Not an error - this directory might not exist on all systems
            return;
        }

        $files = File::files($logDir);
        $sinceDate = $since ? Carbon::parse($since) : null;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'log') {
                continue;
            }

            $fileModTime = Carbon::createFromTimestamp($file->getMTime());

            if ($sinceDate && $fileModTime->isBefore($sinceDate)) {
                continue;
            }

            $this->processClaudeLogFile($file->getPathname(), $dryRun);
        }
    }

    /**
     * Import Claude Projects logs from ~/.claude/projects/
     */
    private function importClaudeProjectsLogs(?string $since, bool $dryRun): void
    {
        $projectsDir = $_SERVER['HOME'].'/.claude/projects';

        if (! File::isDirectory($projectsDir)) {
            $this->stats['errors'][] = "Claude projects directory not found: {$projectsDir}";

            return;
        }

        $sinceDate = $since ? Carbon::parse($since) : null;
        $files = collect();

        // Recursively find all .jsonl files in project directories
        $this->findClaudeProjectFiles($projectsDir, $files, $sinceDate);

        foreach ($files as $file) {
            $this->processClaudeProjectFile($file, $dryRun);
        }
    }

    /**
     * Recursively find Claude project .jsonl files
     */
    private function findClaudeProjectFiles(string $dir, $files, ?Carbon $sinceDate): void
    {
        foreach (File::files($dir) as $file) {
            if ($file->getExtension() === 'jsonl') {
                $fileModTime = Carbon::createFromTimestamp($file->getMTime());
                if (! $sinceDate || $fileModTime->isAfter($sinceDate)) {
                    $files->push($file->getPathname());
                }
            }
        }

        foreach (File::directories($dir) as $subDir) {
            $this->findClaudeProjectFiles($subDir, $files, $sinceDate);
        }
    }

    /**
     * Process individual OpenCode log file
     */
    private function processOpenCodeLogFile(string $filePath, bool $dryRun): void
    {
        $fileName = basename($filePath);
        $fileModTime = Carbon::createFromTimestamp(filemtime($filePath));
        $checksum = hash_file('sha256', $filePath);

        // Check if we've already imported this exact file version
        if (! $dryRun && $this->isFileAlreadyImported($fileName, $checksum)) {
            return;
        }

        $this->stats['files_processed']++;
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $parsed = $this->parseOpenCodeLogLine($line);

            if (! $parsed) {
                continue;
            }

            $logEntry = [
                'source_type' => 'opencode',
                'source_file' => $fileName,
                'file_modified_at' => $fileModTime,
                'file_checksum' => $checksum,
                'file_line_number' => $lineNumber,
                'log_level' => $parsed['level'] ?? null,
                'log_timestamp' => $parsed['timestamp'],
                'service' => $parsed['service'] ?? null,
                'message' => $parsed['message'] ?? null,
                'structured_data' => $parsed['data'] ?? null,
                'session_id' => $this->extractSessionId($parsed),
                'provider' => $this->extractProvider($parsed),
                'model' => $this->extractModel($parsed),
                'tool_calls' => $this->extractToolCalls($parsed),
            ];

            if ($this->hasExistingLogEntry($logEntry)) {
                $this->stats['entries_skipped']++;

                continue;
            }

            if (! $dryRun) {
                AgentLog::create($logEntry);
            }

            $this->stats['entries_imported']++;
        }

        if (! $dryRun) {
            $this->markFileAsImported($fileName, $checksum);
        }
    }

    /**
     * Import Codex CLI logs from ~/.codex/sessions/
     */
    private function importCodexLogs(?string $since, bool $dryRun): void
    {
        $logDir = $_SERVER['HOME'].'/.codex/sessions';

        if (! File::isDirectory($logDir)) {
            $this->stats['errors'][] = "Codex log directory not found: {$logDir}";

            return;
        }

        $sinceDate = $since ? Carbon::parse($since) : null;
        $files = collect();

        // Recursively find all .jsonl files
        $this->findCodexFiles($logDir, $files, $sinceDate);

        foreach ($files as $file) {
            $this->processCodexLogFile($file, $dryRun);
        }
    }

    /**
     * Recursively find Codex .jsonl files
     */
    private function findCodexFiles(string $dir, $files, ?Carbon $sinceDate): void
    {
        foreach (File::files($dir) as $file) {
            if ($file->getExtension() === 'jsonl') {
                $fileModTime = Carbon::createFromTimestamp($file->getMTime());
                if (! $sinceDate || $fileModTime->isAfter($sinceDate)) {
                    $files->push($file->getPathname());
                }
            }
        }

        foreach (File::directories($dir) as $subDir) {
            $this->findCodexFiles($subDir, $files, $sinceDate);
        }
    }

    /**
     * Process individual Codex session file
     */
    private function processCodexLogFile(string $filePath, bool $dryRun): void
    {
        $fileName = basename($filePath);
        $fileModTime = Carbon::createFromTimestamp(filemtime($filePath));
        $checksum = hash_file('sha256', $filePath);

        // Check if we've already imported this exact file version
        if (! $dryRun && $this->isFileAlreadyImported($fileName, $checksum)) {
            return;
        }

        $this->stats['files_processed']++;
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $lineNumber = 0;
        $sessionId = null;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $parsed = $this->parseCodexLogLine($line, $fileName);

            if (! $parsed) {
                continue;
            }

            // Extract session ID from first session_meta entry
            if (! $sessionId && $parsed['type'] === 'session_meta' && isset($parsed['data']['id'])) {
                $sessionId = $parsed['data']['id'];
            }

            $logEntry = [
                'source_type' => 'codex',
                'source_file' => $fileName,
                'file_modified_at' => $fileModTime,
                'file_checksum' => $checksum,
                'file_line_number' => $lineNumber,
                'log_level' => $parsed['level'] ?? 'info',
                'log_timestamp' => $parsed['timestamp'],
                'service' => 'codex_cli',
                'message' => $parsed['message'] ?? null,
                'structured_data' => $parsed['data'] ?? null,
                'session_id' => $sessionId,
                'provider' => $this->extractCodexProvider($parsed),
                'model' => $this->extractCodexModel($parsed),
                'tool_calls' => $this->extractCodexToolCalls($parsed),
            ];

            if ($this->hasExistingLogEntry($logEntry)) {
                $this->stats['entries_skipped']++;

                continue;
            }

            if (! $dryRun) {
                AgentLog::create($logEntry);
            }

            $this->stats['entries_imported']++;
        }

        if (! $dryRun) {
            $this->markFileAsImported($fileName, $checksum);
        }
    }

    /**
     * Parse Codex JSONL log line
     */
    private function parseCodexLogLine(string $line, string $fileName): ?array
    {
        $json = json_decode($line, true);

        if (! $json || ! isset($json['timestamp'], $json['type'])) {
            return null;
        }

        $timestamp = Carbon::parse($json['timestamp']);
        $type = $json['type'];
        $payload = $json['payload'] ?? [];

        // Generate appropriate message based on type
        $message = $this->generateCodexMessage($type, $payload);

        return [
            'timestamp' => $timestamp,
            'type' => $type,
            'message' => $message,
            'data' => [
                'type' => $type,
                'payload' => $payload,
                'raw_line' => $line,
                'session_file' => $fileName,
            ],
        ];
    }

    /**
     * Generate human-readable message from Codex log entry
     */
    private function generateCodexMessage(string $type, array $payload): string
    {
        switch ($type) {
            case 'session_meta':
                $version = $payload['cli_version'] ?? 'unknown';
                $cwd = basename($payload['cwd'] ?? 'unknown');

                return "Session started (v{$version}) in {$cwd}";

            case 'response_item':
                if (isset($payload['role'])) {
                    $role = $payload['role'];
                    $contentLength = isset($payload['content']) ? count($payload['content']) : 0;

                    return ucfirst($role)." message ({$contentLength} content items)";
                }

                return 'Response item';

            case 'tool_use':
                $toolName = $payload['name'] ?? 'unknown';

                return "Tool used: {$toolName}";

            case 'tool_result':
                $status = isset($payload['is_error']) && $payload['is_error'] ? 'error' : 'success';

                return "Tool result: {$status}";

            default:
                return "Codex event: {$type}";
        }
    }

    /**
     * Extract provider from Codex data
     */
    private function extractCodexProvider(array $parsed): ?string
    {
        $data = $parsed['data'] ?? [];

        // Look in session metadata
        if ($parsed['type'] === 'session_meta' && isset($data['payload']['originator'])) {
            return 'anthropic'; // Codex primarily uses Claude
        }

        return 'anthropic';
    }

    /**
     * Extract model from Codex data
     */
    private function extractCodexModel(array $parsed): ?string
    {
        $data = $parsed['data'] ?? [];
        $payload = $data['payload'] ?? [];

        // Look for model info in response items
        if ($parsed['type'] === 'response_item' && isset($payload['model'])) {
            return $payload['model'];
        }

        // Default to Claude for Codex
        return 'claude-3-5-sonnet';
    }

    /**
     * Extract tool calls from Codex data
     */
    private function extractCodexToolCalls(array $parsed): ?array
    {
        $data = $parsed['data'] ?? [];
        $payload = $data['payload'] ?? [];

        if ($parsed['type'] === 'tool_use') {
            return [
                'name' => $payload['name'] ?? null,
                'input' => $payload['input'] ?? null,
            ];
        }

        if ($parsed['type'] === 'tool_result') {
            return [
                'tool_use_id' => $payload['tool_use_id'] ?? null,
                'content' => $payload['content'] ?? null,
                'is_error' => $payload['is_error'] ?? false,
            ];
        }

        return null;
    }

    /**
     * Process individual Claude Project session file
     */
    private function processClaudeProjectFile(string $filePath, bool $dryRun): void
    {
        $fileName = basename($filePath);
        $fileModTime = Carbon::createFromTimestamp(filemtime($filePath));
        $checksum = hash_file('sha256', $filePath);

        // Check if we've already imported this exact file version
        if (! $dryRun && $this->isFileAlreadyImported($fileName, $checksum)) {
            return;
        }

        $this->stats['files_processed']++;
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $lineNumber = 0;
        $sessionId = null;
        $projectPath = null;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $parsed = $this->parseClaudeProjectLine($line, $fileName);

            if (! $parsed) {
                continue;
            }

            // Extract session ID and project info from first entry
            if (! $sessionId && isset($parsed['data']['sessionId'])) {
                $sessionId = $parsed['data']['sessionId'];
            }
            if (! $projectPath && isset($parsed['data']['cwd'])) {
                $projectPath = basename($parsed['data']['cwd']);
            }

            $logEntry = [
                'source_type' => 'claude_projects',
                'source_file' => $fileName,
                'file_modified_at' => $fileModTime,
                'file_checksum' => $checksum,
                'file_line_number' => $lineNumber,
                'log_level' => $parsed['level'] ?? 'info',
                'log_timestamp' => $parsed['timestamp'],
                'service' => 'claude_projects',
                'message' => $parsed['message'] ?? null,
                'structured_data' => $parsed['data'] ?? null,
                'session_id' => $sessionId,
                'provider' => 'anthropic',
                'model' => $this->extractClaudeProjectModel($parsed),
                'tool_calls' => $this->extractClaudeProjectToolCalls($parsed),
            ];

            if ($this->hasExistingLogEntry($logEntry)) {
                $this->stats['entries_skipped']++;

                continue;
            }

            if (! $dryRun) {
                AgentLog::create($logEntry);
            }

            $this->stats['entries_imported']++;
        }

        if (! $dryRun) {
            $this->markFileAsImported($fileName, $checksum);
        }
    }

    /**
     * Parse Claude Project JSONL log line
     */
    private function parseClaudeProjectLine(string $line, string $fileName): ?array
    {
        $json = json_decode($line, true);

        if (! $json || ! isset($json['timestamp'], $json['type'])) {
            return null;
        }

        $timestamp = Carbon::parse($json['timestamp']);
        $type = $json['type'];
        $message = $json['message'] ?? [];

        // Generate appropriate message based on type and content
        $humanMessage = $this->generateClaudeProjectMessage($type, $message, $json);

        return [
            'timestamp' => $timestamp,
            'type' => $type,
            'message' => $humanMessage,
            'data' => [
                'type' => $type,
                'sessionId' => $json['sessionId'] ?? null,
                'cwd' => $json['cwd'] ?? null,
                'gitBranch' => $json['gitBranch'] ?? null,
                'version' => $json['version'] ?? null,
                'uuid' => $json['uuid'] ?? null,
                'parentUuid' => $json['parentUuid'] ?? null,
                'isMeta' => $json['isMeta'] ?? false,
                'message' => $message,
                'raw_line' => $line,
                'session_file' => $fileName,
            ],
        ];
    }

    /**
     * Generate human-readable message from Claude Project log entry
     */
    private function generateClaudeProjectMessage(string $type, mixed $message, array $json): string
    {
        $role = (string) ($this->getClaudeProjectMessageValue($message, 'role') ?? ($json['role'] ?? 'unknown'));
        $rawContent = $this->getClaudeProjectMessageValue($message, 'content', '');
        $content = $this->stringifyClaudeProjectContent($rawContent);

        switch ($type) {
            case 'user':
                if ($json['isMeta'] ?? false) {
                    return 'Meta message: '.$this->summariseContent($content);
                }

                // Check for command format
                if (str_contains($content, '<command-name>')) {
                    preg_match('/<command-name>([^<]+)<\/command-name>/', $content, $matches);
                    $commandName = $matches[1] ?? 'unknown';

                    return "User command: {$commandName}";
                }

                return 'User: '.$this->summariseContent($content);

            case 'assistant':
                $contentLength = mb_strlen($content, 'UTF-8');

                return "Assistant response ({$contentLength} chars)";

            case 'tool_use':
                $toolName = (string) ($this->getClaudeProjectMessageValue($message, 'name') ?? 'unknown');

                return "Tool used: {$toolName}";

            case 'tool_result':
                $isError = (bool) ($this->getClaudeProjectMessageValue($message, 'is_error') ?? false);
                $summary = $this->summariseContent($content, 80);

                return $isError ? "Tool result (error): {$summary}" : "Tool result: {$summary}";

            default:
                return "Claude event: {$type}";
        }
    }

    /**
     * Extract a target field from a Claude message payload regardless of structure
     */
    private function getClaudeProjectMessageValue(mixed $message, string $key, mixed $default = null): mixed
    {
        if (! is_array($message)) {
            return $default;
        }

        if (array_key_exists($key, $message)) {
            return $message[$key];
        }

        if (! $this->isAssociativeArray($message)) {
            foreach ($message as $item) {
                if (is_array($item) && array_key_exists($key, $item)) {
                    return $item[$key];
                }
            }
        }

        return $default;
    }

    /**
     * Convert Claude content payload into a plain string for summarisation
     */
    private function stringifyClaudeProjectContent(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            // Handle associative array with direct text
            if ($this->isAssociativeArray($content) && array_key_exists('text', $content)) {
                return (string) $content['text'];
            }

            $parts = [];

            foreach ($content as $segment) {
                if (is_string($segment)) {
                    $parts[] = $segment;

                    continue;
                }

                if (is_array($segment)) {
                    if (array_key_exists('text', $segment)) {
                        $parts[] = (string) $segment['text'];

                        continue;
                    }

                    if (($segment['type'] ?? null) === 'code' && isset($segment['code'])) {
                        $parts[] = (string) $segment['code'];

                        continue;
                    }

                    $parts[] = json_encode($segment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    continue;
                }

                if (is_bool($segment) || $segment === null) {
                    $parts[] = json_encode($segment);

                    continue;
                }

                $parts[] = (string) $segment;
            }

            $text = trim(implode("\n", array_filter($parts, fn ($value) => $value !== null && $value !== '')));

            if ($text !== '') {
                return $text;
            }

            return (string) json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($content) || $content === null) {
            return json_encode($content);
        }

        return (string) $content;
    }

    /**
     * Provide a short summary of content with ellipsis when truncated
     */
    private function summariseContent(string $content, int $limit = 100): string
    {
        $trimmed = trim($content);

        if ($trimmed === '') {
            return 'No content';
        }

        if (mb_strlen($trimmed, 'UTF-8') <= $limit) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, $limit, 'UTF-8').'...';
    }

    /**
     * Determine if array is associative
     */
    private function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Extract model from Claude Project data
     */
    private function extractClaudeProjectModel(array $parsed): ?string
    {
        // Claude Projects typically use Claude-3.5-Sonnet
        return 'claude-3-5-sonnet';
    }

    /**
     * Extract tool calls from Claude Project data
     */
    private function extractClaudeProjectToolCalls(array $parsed): ?array
    {
        $data = $parsed['data'] ?? [];
        $message = $data['message'] ?? [];

        if ($parsed['type'] === 'tool_use') {
            return [
                'name' => $message['name'] ?? null,
                'input' => $message['input'] ?? null,
            ];
        }

        if ($parsed['type'] === 'tool_result') {
            return [
                'tool_use_id' => $message['tool_use_id'] ?? null,
                'content' => $message['content'] ?? null,
                'is_error' => $message['is_error'] ?? false,
            ];
        }

        return null;
    }

    /**
     * Process individual Claude log file
     */
    private function processClaudeLogFile(string $filePath, bool $dryRun): void
    {
        $fileName = basename($filePath);
        $fileModTime = Carbon::createFromTimestamp(filemtime($filePath));
        $checksum = hash_file('sha256', $filePath);

        // Check if we've already imported this exact file version
        if (! $dryRun && $this->isFileAlreadyImported($fileName, $checksum)) {
            return;
        }

        $this->stats['files_processed']++;
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $lineNumber = 0;

        $sourceType = str_contains($fileName, 'mcp') ? 'claude_mcp' : 'claude_desktop';

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $parsed = $this->parseClaudeLogLine($line, $sourceType);

            if (! $parsed) {
                continue;
            }

            $logEntry = [
                'source_type' => $sourceType,
                'source_file' => $fileName,
                'file_modified_at' => $fileModTime,
                'file_checksum' => $checksum,
                'file_line_number' => $lineNumber,
                'log_level' => $parsed['level'] ?? null,
                'log_timestamp' => $parsed['timestamp'],
                'service' => $parsed['service'] ?? null,
                'message' => $parsed['message'] ?? null,
                'structured_data' => $parsed['data'] ?? null,
                'session_id' => $this->extractSessionId($parsed),
                'provider' => $this->extractProvider($parsed),
                'model' => $this->extractModel($parsed),
                'tool_calls' => $this->extractToolCalls($parsed),
            ];

            if ($this->hasExistingLogEntry($logEntry)) {
                $this->stats['entries_skipped']++;

                continue;
            }

            if (! $dryRun) {
                AgentLog::create($logEntry);
            }

            $this->stats['entries_imported']++;
        }

        if (! $dryRun) {
            $this->markFileAsImported($fileName, $checksum);
        }
    }

    /**
     * Parse OpenCode log line
     */
    private function parseOpenCodeLogLine(string $line): ?array
    {
        // OpenCode format: LEVEL TIMESTAMP +offset service=name message
        if (! preg_match('/^(\w+)\s+([0-9T:-]+)\s+\+(\d+)ms\s+(.+)$/', $line, $matches)) {
            return null;
        }

        $level = $matches[1];
        $timestamp = Carbon::parse($matches[2]);
        $offset = (int) $matches[3];
        $rest = $matches[4];

        // Parse service=name and message
        $service = null;
        $message = $rest;

        if (preg_match('/^service=(\S+)\s+(.+)$/', $rest, $serviceMatches)) {
            $service = $serviceMatches[1];
            $message = $serviceMatches[2];
        }

        return [
            'level' => strtolower($level),
            'timestamp' => $timestamp,
            'service' => $service,
            'message' => $message,
            'data' => [
                'offset_ms' => $offset,
                'raw_line' => $line,
            ],
        ];
    }

    /**
     * Parse Claude log line
     */
    private function parseClaudeLogLine(string $line, string $sourceType): ?array
    {
        if ($sourceType === 'claude_mcp') {
            // MCP format: TIMESTAMP [level] [service] message
            if (! preg_match('/^([0-9T:.-]+Z)\s+\[(\w+)\]\s+\[([^\]]+)\]\s+(.+)$/', $line, $matches)) {
                return null;
            }

            return [
                'level' => strtolower($matches[2]),
                'timestamp' => Carbon::parse($matches[1]),
                'service' => $matches[3],
                'message' => $matches[4],
                'data' => [
                    'raw_line' => $line,
                ],
            ];
        } else {
            // Main log format: TIMESTAMP [level] message
            if (! preg_match('/^([0-9: -]+)\s+\[(\w+)\]\s+(.+)$/', $line, $matches)) {
                return null;
            }

            return [
                'level' => strtolower($matches[2]),
                'timestamp' => Carbon::parse($matches[1]),
                'service' => 'claude_desktop',
                'message' => $matches[3],
                'data' => [
                    'raw_line' => $line,
                ],
            ];
        }
    }

    /**
     * Extract session ID from parsed log data
     */
    private function extractSessionId(array $parsed): ?string
    {
        // Look for session identifiers in the message or structured data
        $message = $parsed['message'] ?? '';

        if (preg_match('/session[_-]?id[=:]([a-f0-9-]+)/i', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract provider from parsed log data
     */
    private function extractProvider(array $parsed): ?string
    {
        $message = $parsed['message'] ?? '';

        if (preg_match('/provider[_-]?id[=:](\w+)/i', $message, $matches)) {
            return $matches[1];
        }

        if (str_contains($message, 'anthropic')) {
            return 'anthropic';
        }

        if (str_contains($message, 'openai')) {
            return 'openai';
        }

        if (str_contains($message, 'ollama')) {
            return 'ollama';
        }

        return null;
    }

    /**
     * Extract model from parsed log data
     */
    private function extractModel(array $parsed): ?string
    {
        $message = $parsed['message'] ?? '';

        if (preg_match('/model[_-]?id[=:]([a-z0-9.-]+)/i', $message, $matches)) {
            return $matches[1];
        }

        if (str_contains($message, 'claude-')) {
            if (preg_match('/(claude-[a-z0-9.-]+)/', $message, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract tool calls from parsed log data
     */
    private function extractToolCalls(array $parsed): ?array
    {
        $message = $parsed['message'] ?? '';

        // Look for tool-related messages
        if (str_contains($message, 'tool') && str_contains($message, '"name"')) {
            // Try to parse JSON from the message
            if (preg_match('/\{.*"name".*\}/', $message, $matches)) {
                $json = json_decode($matches[0], true);
                if ($json && isset($json['name'])) {
                    return $json;
                }
            }
        }

        return null;
    }

    /**
     * Check if file has already been imported
     */
    private function isFileAlreadyImported(string $fileName, string $checksum): bool
    {
        return AgentLog::where('source_file', $fileName)
            ->where('file_checksum', $checksum)
            ->exists();
    }

    /**
     * Mark file as imported (in-memory tracking for this session)
     */
    private function markFileAsImported(string $fileName, string $checksum): void
    {
        $this->importedFiles[$fileName] = $checksum;
    }

    /**
     * Determine whether a log entry has already been persisted
     */
    private function hasExistingLogEntry(array $logEntry): bool
    {
        $query = AgentLog::query()
            ->where('source_type', $logEntry['source_type'])
            ->where('source_file', $logEntry['source_file'])
            ->where('log_timestamp', $logEntry['log_timestamp']);

        if (array_key_exists('file_line_number', $logEntry) && $logEntry['file_line_number'] !== null) {
            $query->where('file_line_number', $logEntry['file_line_number']);
        } elseif (! empty($logEntry['message'])) {
            // Fall back to message matching when line numbers are unavailable
            $query->where('message', $logEntry['message']);
        }

        if (! empty($logEntry['session_id'])) {
            $query->where('session_id', $logEntry['session_id']);
        }

        return $query->exists();
    }

    /**
     * Get import statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
