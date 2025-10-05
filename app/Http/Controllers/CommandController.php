<?php

namespace App\Http\Controllers;

use App\DTOs\CommandRequest;
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

        // Parse arguments from the raw string
        if (! empty($rawArguments) && empty($arguments)) {
            $arguments = $this->parseArguments($rawArguments);
        }

        try {
            $startTime = microtime(true);

            // First try to find in hardcoded commands
            try {
                $commandClass = CommandRegistry::find($commandName);

                // Create command request
                $commandRequest = new CommandRequest(
                    command: $commandName,
                    arguments: $arguments,
                    raw: $commandText
                );

                // Execute the command
                $commandInstance = app($commandClass);
                $response = $commandInstance->handle($commandRequest);

                $executionTime = round((microtime(true) - $startTime) * 1000, 2); // milliseconds

                // Log command execution as a fragment for activity tracking
                $this->logCommandExecution($commandName, $commandText, $arguments, $response, $executionTime, true);

                return response()->json([
                    'success' => true,
                    'type' => $response->type,
                    'message' => $response->message,
                    'fragments' => $response->fragments,
                    'shouldResetChat' => $response->shouldResetChat,
                    'shouldOpenPanel' => $response->shouldOpenPanel,
                    'panelData' => $response->panelData,
                    'shouldShowSuccessToast' => $response->shouldShowSuccessToast,
                    'toastData' => $response->toastData,
                    'shouldShowErrorToast' => $response->shouldShowErrorToast,
                    'data' => $response->data,
                ]);

            } catch (\InvalidArgumentException $e) {
                // Not found in hardcoded commands, try file-based commands
                $dbCommand = CommandRegistryModel::where('slug', $commandName)->first();
                if ($dbCommand) {
                    $runner = app(CommandRunner::class);
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
                    // Command not found in either system
                    throw $e;
                }
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

        // Simple argument parsing - can be enhanced
        // For now, just split by spaces and handle key:value pairs
        $parts = explode(' ', $rawArguments);

        foreach ($parts as $part) {
            if (str_contains($part, ':')) {
                [$key, $value] = explode(':', $part, 2);
                $arguments[$key] = $value;
            } else {
                // Treat as positional argument or add to 'identifier'
                if (! isset($arguments['identifier'])) {
                    $arguments['identifier'] = $part;
                } else {
                    $arguments['identifier'] .= ' '.$part;
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

        // Look for response-generating steps (notify, response.panel, etc.)
        foreach ($result['steps'] as $step) {
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
            if ($step['type'] === 'response.panel' && isset($stepOutput['shouldOpenPanel'])) {
                $response->type = $stepOutput['type'] ?? 'panel';
                $response->message = $stepOutput['message'] ?? 'Panel response';
                $response->shouldOpenPanel = $stepOutput['shouldOpenPanel'];
                $response->panelData = $stepOutput['panel_data'] ?? null;
            }

            // Handle condition steps that may contain response steps
            if ($step['type'] === 'condition' && isset($stepOutput['steps_executed'])) {
                foreach ($stepOutput['steps_executed'] as $subStep) {
                    $subStepOutput = $subStep['output'] ?? [];

                    // Check for response.panel in condition branches
                    if ($subStep['type'] === 'response.panel' && isset($subStepOutput['shouldOpenPanel'])) {
                        $response->type = $subStepOutput['type'] ?? 'panel';
                        $response->message = $subStepOutput['message'] ?? 'Panel response';
                        $response->shouldOpenPanel = $subStepOutput['shouldOpenPanel'];
                        $response->panelData = $subStepOutput['panel_data'] ?? null;
                    }

                    // Check for notify in condition branches
                    if ($subStep['type'] === 'notify' && isset($subStepOutput['message'])) {
                        $response->message = $subStepOutput['message'];
                        if (isset($subStepOutput['level']) && $subStepOutput['level'] === 'success') {
                            $response->shouldShowSuccessToast = true;
                        } elseif (isset($subStepOutput['level']) && $subStepOutput['level'] === 'error') {
                            $response->shouldShowErrorToast = true;
                        }
                    }
                }
            }
        }

        return $response;
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
}
