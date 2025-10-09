<?php

namespace App\Listeners;

use App\Models\CommandAuditLog;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\LogActivity;

class CommandLoggingListener
{
    private static array $commandStarts = [];

    private static array $destructiveCommands = [
        'migrate:fresh',
        'migrate:reset',
        'migrate:rollback',
        'db:wipe',
        'db:seed',
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
        'queue:flush',
        'queue:clear',
        'telescope:prune',
        'horizon:purge',
    ];

    public function handleCommandStarting(CommandStarting $event): void
    {
        $commandName = $event->command ?? 'unknown';

        self::$commandStarts[$commandName] = [
            'started_at' => now(),
            'command' => $commandName,
            'input' => $event->input,
        ];

        $log = CommandAuditLog::create([
            'command_name' => $commandName,
            'command_signature' => $this->getCommandSignature($event),
            'arguments' => $this->sanitizeArguments($event->input->getArguments()),
            'options' => $this->sanitizeOptions($event->input->getOptions()),
            'status' => 'running',
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'is_destructive' => $this->isDestructiveCommand($commandName),
            'started_at' => now(),
        ]);

        self::$commandStarts[$commandName]['log_id'] = $log->id;
    }

    public function handleCommandFinished(CommandFinished $event): void
    {
        $commandName = $event->command ?? 'unknown';
        $startData = self::$commandStarts[$commandName] ?? null;

        if (! $startData) {
            return;
        }

        $executionTime = (int) now()->diffInMilliseconds($startData['started_at']);

        if (isset($startData['log_id'])) {
            $log = CommandAuditLog::find($startData['log_id']);
            if ($log) {
                $log->update([
                    'status' => $event->exitCode === 0 ? 'completed' : 'failed',
                    'exit_code' => $event->exitCode,
                    'execution_time_ms' => $executionTime,
                    'completed_at' => now(),
                ]);

                if ($log->is_destructive) {
                    $this->logDestructiveCommand($log, $event);
                }
            }
        }

        unset(self::$commandStarts[$commandName]);
    }

    private function isDestructiveCommand(string $commandName): bool
    {
        foreach (self::$destructiveCommands as $pattern) {
            if (str_starts_with($commandName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function getCommandSignature($event): string
    {
        $command = $event->command ?? 'unknown';
        $args = collect($event->input->getArguments())
            ->filter(fn ($v, $k) => $k !== 'command')
            ->values()
            ->implode(' ');
        $opts = collect($event->input->getOptions())
            ->filter(fn ($v) => $v !== false && $v !== null && $v !== [])
            ->map(function ($v, $k) {
                if (is_bool($v)) {
                    return "--{$k}";
                }
                if (is_array($v)) {
                    return "--{$k}=" . json_encode($v);
                }
                return "--{$k}={$v}";
            })
            ->values()
            ->implode(' ');

        return trim("{$command} {$args} {$opts}");
    }

    private function sanitizeArguments(array $arguments): array
    {
        unset($arguments['command']);

        return array_map(function ($value) {
            if (is_string($value) && (
                str_contains(strtolower($value), 'password') ||
                str_contains(strtolower($value), 'secret') ||
                str_contains(strtolower($value), 'token')
            )) {
                return '***REDACTED***';
            }

            return $value;
        }, $arguments);
    }

    private function sanitizeOptions(array $options): array
    {
        return array_map(function ($value, $key) {
            if (in_array(strtolower($key), ['password', 'secret', 'token', 'key'])) {
                return '***REDACTED***';
            }

            return $value;
        }, $options, array_keys($options));
    }

    private function logDestructiveCommand(CommandAuditLog $log, CommandFinished $event): void
    {
        $user = $log->user;
        $userName = $user ? $user->name : 'System/CLI';

        activity()
            ->causedBy($log->user_id)
            ->withProperties([
                'command' => $log->command_name,
                'signature' => $log->command_signature,
                'exit_code' => $event->exitCode,
                'execution_time_ms' => $log->execution_time_ms,
                'ip_address' => $log->ip_address,
            ])
            ->event('destructive_command')
            ->log("Destructive command executed: {$log->command_name} by {$userName}");

        if (config('audit.notifications.enabled', true)) {
            $notifiables = $this->getNotificationRecipients();
            foreach ($notifiables as $notifiable) {
                $notifiable->notify(new \App\Notifications\DestructiveCommandExecuted($log, $event->exitCode));
            }
        }
    }

    private function getNotificationRecipients(): array
    {
        $recipients = [];

        if ($adminEmail = config('audit.notifications.admin_email')) {
            $admin = \App\Models\User::where('email', $adminEmail)->first();
            if ($admin) {
                $recipients[] = $admin;
            }
        }

        return $recipients;
    }
}
