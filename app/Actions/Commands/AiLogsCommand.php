<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\AgentLog;

class AiLogsCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $logs = AgentLog::query()
            ->orderByDesc('log_timestamp')
            ->take(200)
            ->get();

        if ($logs->isEmpty()) {
            return new CommandResponse(
                message: '📋 No AI logs found.',
                type: 'ailogs',
                fragments: [],
                shouldResetChat: false,
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'list',
                    'message' => '📋 No AI logs found.',
                    'logs' => [],
                ],
            );
        }

        $logData = $logs->map(function (AgentLog $log) {
            $structuredData = $log->structured_data ?? [];
            
            return [
                'id' => $log->id,
                'source_type' => $log->source_type,
                'source_file' => $log->source_file,
                'log_level' => $log->log_level,
                'log_timestamp' => $log->log_timestamp?->toIso8601String(),
                'service' => $log->service,
                'message' => $log->message,
                'session_id' => $log->session_id,
                'provider' => $log->provider,
                'model' => $log->model,
                'tool_calls' => $log->tool_calls,
                'structured_data' => $structuredData,
                'file_modified_at' => $log->file_modified_at?->toIso8601String(),
                'created_at' => $log->created_at?->toIso8601String(),
                'expanded_content' => $this->generateExpandedContent($log),
            ];
        })->all();

        return new CommandResponse(
            message: '📋 Found **'.count($logData).'** AI log'.(count($logData) !== 1 ? 's' : ''),
            type: 'ailogs',
            fragments: [],
            shouldResetChat: false,
            shouldOpenPanel: true,
            panelData: [
                'action' => 'list',
                'message' => '📋 Found **'.count($logData).'** AI log'.(count($logData) !== 1 ? 's' : ''),
                'logs' => $logData,
            ],
        );
    }

    /**
     * Generate expanded content for the log entry
     */
    private function generateExpandedContent(AgentLog $log): string
    {
        $content = [];

        // Basic info
        $content[] = "**Source:** {$log->source_type}";
        $content[] = "**File:** {$log->source_file}";
        $content[] = "**Level:** " . strtoupper($log->log_level ?? 'INFO');
        $content[] = "**Timestamp:** {$log->log_timestamp}";
        
        if ($log->service) {
            $content[] = "**Service:** {$log->service}";
        }

        if ($log->session_id) {
            $content[] = "**Session:** {$log->session_id}";
        }

        if ($log->provider) {
            $content[] = "**Provider:** {$log->provider}";
        }

        if ($log->model) {
            $content[] = "**Model:** {$log->model}";
        }

        // Message
        if ($log->message) {
            $content[] = "**Message:**";
            $content[] = $log->message;
        }

        // Tool calls
        if ($log->tool_calls) {
            $content[] = "**Tool Calls:**";
            $content[] = "```json\n" . json_encode($log->tool_calls, JSON_PRETTY_PRINT) . "\n```";
        }

        // Structured data
        if ($log->structured_data) {
            $content[] = "**Structured Data:**";
            $content[] = "```json\n" . json_encode($log->structured_data, JSON_PRETTY_PRINT) . "\n```";
        }

        return implode("\n\n", $content);
    }
}