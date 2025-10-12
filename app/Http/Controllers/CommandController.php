<?php

namespace App\Http\Controllers;


use App\Models\Fragment;
use App\Services\CommandRegistry;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    public function execute(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'arguments' => 'array',
        ]);

        $commandText = $request->input('command');
        $arguments = $request->input('arguments', []);

        // Parse command if it comes with slash prefix
        if (str_starts_with($commandText, '/')) {
            $commandText = substr($commandText, 1);
        }

        // Split command and arguments from text
        $parts = explode(' ', $commandText, 2);
        $commandName = $parts[0];
        $rawArguments = $parts[1] ?? '';

        // Handle common aliases for YAML commands
        $commandName = $this->resolveAlias($commandName);

        // Parse arguments from the raw string
        if (! empty($rawArguments) && empty($arguments)) {
            $arguments = $this->parseArguments($rawArguments);
        }

        // Add additional context from request
        $additionalContext = [];
        if ($request->has('vault_id')) {
            $additionalContext['vault_id'] = $request->input('vault_id');
        }
        if ($request->has('session_id')) {
            $additionalContext['session_id'] = $request->input('session_id');
        }
        if ($request->has('project_id')) {
            $additionalContext['project_id'] = $request->input('project_id');
        }

        // Merge arguments with additional context
        $arguments = array_merge($arguments, $additionalContext);

        try {
            $startTime = microtime(true);

            // Check for PHP command first (new system)
            if (CommandRegistry::isPhpCommand($commandName)) {
                $commandClass = CommandRegistry::getPhpCommand($commandName);
                $commandModel = CommandRegistry::getCommand($commandName);
                
                // Instantiate command with parsed arguments array
                $command = new $commandClass($arguments);
                $command->setContext('web');
                
                // Inject Command and Type models
                if ($commandModel) {
                    $command->setCommand($commandModel);
                }
                
                $result = $command->handle();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                // Format response to match expected frontend structure
                $response = [
                    'success' => true,
                    'type' => $result['type'] ?? 'unknown',
                    'component' => $result['component'] ?? null,
                    'data' => $result['data'] ?? null,
                    'config' => $result['config'] ?? null,
                    'message' => $result['message'] ?? null,
                    'fragments' => [],
                    'shouldResetChat' => false,
                    'shouldOpenPanel' => true,
                    'panelData' => $result['panelData'] ?? $result['data'] ?? null, // Use panelData if provided, fallback to data
                    'shouldShowSuccessToast' => false,
                    'toastData' => null,
                    'shouldShowErrorToast' => false,
                    'execution_time' => $executionTime,
                ];

                // Log command execution
                $this->logCommandExecution($commandName, $commandText, $arguments, $response, $executionTime, true);

                return response()->json($response);
            }

            // Command not found
            throw new \InvalidArgumentException("Command not recognized: {$commandName}");

        } catch (\InvalidArgumentException $e) {
            // Log failed command execution
            $this->logCommandExecution($commandName, $commandText, $arguments, null, 0, false, $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'type' => 'error',
                'message' => "Command not found: {$commandName}. Type `/help` to see available commands.",
            ], 400);

        } catch (\Exception $e) {
            // Log failed command execution
            $this->logCommandExecution($commandName, $commandText, $arguments, null, 0, false, $e->getMessage());

            \Log::error('Command execution failed', [
                'command' => $commandName,
                'arguments' => $arguments,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Command execution failed',
                'type' => 'error',
                'message' => "Failed to execute command: {$commandName}",
            ], 500);
        }
    }

    private function parseArguments(string $rawArguments): array
    {
        $arguments = [];
        $positionalIndex = 0;

        // Simple argument parsing - can be enhanced
        // For now, just split by spaces and handle key:value pairs
        $parts = explode(' ', $rawArguments);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }
            
            if (str_contains($part, ':')) {
                [$key, $value] = explode(':', $part, 2);
                $arguments[$key] = $value;
            } else {
                // Add as indexed argument
                $arguments[$positionalIndex] = $part;
                $positionalIndex++;
            }
        }

        return $arguments;
    }



    /**
     * Log command execution as a fragment for activity tracking
     */
    private function logCommandExecution(
        string $commandName,
        string $commandText,
        array $arguments,
        $response = null,
        float $executionTimeMs = 0,
        bool $success = true,
        ?string $errorMessage = null
    ): void {
        try {
            $fragment = new Fragment;
            $fragment->message = $commandText;
            $fragment->title = "Command: /{$commandName}";
            $fragment->type = 'command';

            // Build metadata for activity tracking
            $responseType = null;
            if (is_array($response)) {
                $responseType = $response['type'] ?? null;
            } elseif (is_object($response)) {
                $responseType = $response->type ?? null;
            }
            
            $metadata = [
                'turn' => 'command',
                'command_name' => $commandName,
                'command_arguments' => $arguments,
                'execution_time_ms' => $executionTimeMs,
                'success' => $success,
                'response_type' => $responseType,
                'timestamp' => now()->toISOString(),
            ];

            if (! $success && $errorMessage) {
                $metadata['error'] = $errorMessage;
            }

            if ($success && $response) {
                if (is_array($response)) {
                    $metadata['response_data'] = [
                        'type' => $response['type'] ?? null,
                        'fragments_count' => is_array($response['fragments'] ?? null) ? count($response['fragments']) : 0,
                        'has_panel_data' => ! empty($response['panelData'] ?? null),
                    ];
                } else {
                    $metadata['response_data'] = [
                        'type' => $response->type ?? null,
                        'fragments_count' => is_array($response->fragments ?? null) ? count($response->fragments) : 0,
                        'has_panel_data' => ! empty($response->panelData ?? null),
                    ];
                }
            }

            // Add session context if available from request
            if ($sessionId = request()->header('X-Session-ID')) {
                $metadata['session_id'] = $sessionId;
            }

            $fragment->metadata = $metadata;
            $fragment->save();

        } catch (\Exception $e) {
            // Don't fail the command if logging fails
            \Log::warning('Failed to log command execution', [
                'command' => $commandName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve command aliases to their actual command names
     */
    private function resolveAlias(string $commandName): string
    {
        $aliases = [
            't' => 'todo',
            's' => 'search',
            'j' => 'join',
        ];

        return $aliases[$commandName] ?? $commandName;
    }
}
