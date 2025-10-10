<?php

namespace App\Http\Controllers;

use App\Decorators\CommandTelemetryDecorator;
use App\Models\CommandRegistry as CommandRegistryModel;
use App\Models\Fragment;
use App\Services\CommandRegistry;
use App\Services\Commands\DSL\CommandRunner;
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
                // Instantiate command with parsed arguments array
                $command = new $commandClass($arguments);
                $command->setContext('web');
                $result = $command->handle();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                // Format response to match expected frontend structure
                $response = [
                    'success' => true,
                    'type' => $result['type'] ?? 'unknown',
                    'component' => $result['component'] ?? null,
                    'data' => $result['data'] ?? null,
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

            // Try to find YAML command (legacy system)
            $dbCommand = CommandRegistryModel::where('slug', $commandName)->first();
            if ($dbCommand) {
                $runner = app(CommandRunner::class);

                // Wrap runner with telemetry if enabled
                if (config('command-telemetry.enabled', true)) {
                    $runner = CommandTelemetryDecorator::wrap($runner);
                }

                $result = $runner->execute($commandName, $arguments);

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                // Convert CommandRunner result to expected response format
                $response = $this->convertDslResultToResponse($result);

                // Log command execution
                $this->logCommandExecution($commandName, $commandText, $arguments, $response, $executionTime, $result['success']);

                return response()->json([
                    'success' => $result['success'],
                    'type' => $response->type,
                    'message' => $response->message,
                    'fragments' => $response->fragments ?? [],
                    'shouldResetChat' => $response->shouldResetChat ?? false,
                    'shouldOpenPanel' => $response->shouldOpenPanel ?? false,
                    'panelData' => $response->panelData ?? null,
                    'shouldShowSuccessToast' => $response->shouldShowSuccessToast ?? false,
                    'toastData' => $response->toastData ?? null,
                    'shouldShowErrorToast' => $response->shouldShowErrorToast ?? false,
                    'data' => $response->data ?? null,
                ]);
            } else {
                // Command not found
                throw new \InvalidArgumentException("Command not recognized: {$commandName}");
            }

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
                // Add as indexed argument for new unified commands
                $arguments[$positionalIndex] = $part;
                $positionalIndex++;
                
                // Also add to 'body' for YAML template compatibility
                if (! isset($arguments['body'])) {
                    $arguments['body'] = $part;
                } else {
                    $arguments['body'] .= ' '.$part;
                }
            }
        }

        return $arguments;
    }

    /**
     * Convert CommandRunner DSL result to expected response format
     */
    private function convertDslResultToResponse(array $result): object
    {
        $response = new \stdClass;
        $response->type = 'success';
        $response->message = 'Command executed successfully';
        $response->fragments = [];
        $response->shouldResetChat = false;
        $response->shouldOpenPanel = false;
        $response->panelData = null;
        $response->shouldShowSuccessToast = false;
        $response->toastData = null;
        $response->shouldShowErrorToast = false;
        $response->data = null;

        if (! $result['success']) {
            $response->type = 'error';
            $response->message = $result['error'] ?? 'Command execution failed';
            $response->shouldShowErrorToast = true;

            return $response;
        }

        // Look for response-generating steps (notify, response.panel, etc.) recursively
        $this->processStepsForResponse($result['steps'], $response);

        return $response;
    }

    /**
     * Process steps recursively to find response-generating steps
     */
    private function processStepsForResponse(array $steps, object $response): void
    {
        foreach ($steps as $step) {
            $stepOutput = $step['output'] ?? [];

            // Handle notify steps with panel_data for navigation
            if ($step['type'] === 'notify' && isset($stepOutput['panel_data'])) {
                $panelData = $stepOutput['panel_data'];
                if (isset($panelData['action']) && $panelData['action'] === 'navigate') {
                    $response->shouldOpenPanel = true;
                    $response->panelData = $panelData;
                    $response->message = $stepOutput['message'] ?? 'Navigating...';
                }
            }

            // Handle notify steps with response_data
            if ($step['type'] === 'notify' && isset($stepOutput['response_data'])) {
                $responseData = $stepOutput['response_data'];

                // Apply response_data properties to the response
                if (isset($responseData['type'])) {
                    $response->type = $responseData['type'];
                }
                if (isset($responseData['shouldResetChat'])) {
                    $response->shouldResetChat = $responseData['shouldResetChat'];
                }
                if (isset($responseData['shouldOpenPanel'])) {
                    $response->shouldOpenPanel = $responseData['shouldOpenPanel'];
                }
                if (isset($responseData['panelData'])) {
                    $response->panelData = $responseData['panelData'];
                }
                if (isset($responseData['shouldShowSuccessToast'])) {
                    $response->shouldShowSuccessToast = $responseData['shouldShowSuccessToast'];
                }
                if (isset($responseData['shouldShowErrorToast'])) {
                    $response->shouldShowErrorToast = $responseData['shouldShowErrorToast'];
                }
                if (isset($responseData['toastData'])) {
                    $response->toastData = $responseData['toastData'];
                }

                // Use the notify message if provided, otherwise keep existing
                if (isset($stepOutput['message']) && ! empty($stepOutput['message'])) {
                    $response->message = $stepOutput['message'];
                }
            }

            // Handle simple notify steps (just message)
            if ($step['type'] === 'notify' && isset($stepOutput['message']) &&
                ! isset($stepOutput['panel_data']) && ! isset($stepOutput['response_data'])) {
                $response->message = $stepOutput['message'];
                if (isset($stepOutput['level']) && $stepOutput['level'] === 'success') {
                    $response->shouldShowSuccessToast = true;
                } elseif (isset($stepOutput['level']) && $stepOutput['level'] === 'error') {
                    $response->shouldShowErrorToast = true;
                }
            }

            // Handle response.panel steps
            if ($step['type'] === 'response.panel') {
                $response->type = $stepOutput['type'] ?? 'panel';
                $response->message = $stepOutput['message'] ?? 'Panel response';
                $response->shouldOpenPanel = true; // Always open panel for response.panel steps
                $response->panelData = $stepOutput['panel_data'] ?? $stepOutput;
            }

            // Recursively process sub-steps (condition branches, etc.)
            if (isset($stepOutput['steps_executed'])) {
                $this->processStepsForResponse($stepOutput['steps_executed'], $response);
            }
        }
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
            $metadata = [
                'turn' => 'command',
                'command_name' => $commandName,
                'command_arguments' => $arguments,
                'execution_time_ms' => $executionTimeMs,
                'success' => $success,
                'response_type' => $response?->type ?? null,
                'timestamp' => now()->toISOString(),
            ];

            if (! $success && $errorMessage) {
                $metadata['error'] = $errorMessage;
            }

            if ($success && $response) {
                $metadata['response_data'] = [
                    'type' => $response->type,
                    'fragments_count' => is_array($response->fragments) ? count($response->fragments) : 0,
                    'has_panel_data' => ! empty($response->panelData),
                ];
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
