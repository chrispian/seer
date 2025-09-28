<?php

namespace App\Http\Controllers;

use App\DTOs\CommandRequest;
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

        // Parse arguments from the raw string
        if (! empty($rawArguments) && empty($arguments)) {
            $arguments = $this->parseArguments($rawArguments);
        }

        try {
            // Find the command class
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
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'type' => 'error',
                'message' => "Command not found: {$commandName}. Type `/help` to see available commands.",
            ], 400);

        } catch (\Exception $e) {
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
}
