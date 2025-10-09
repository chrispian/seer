<?php

namespace App\Notifications;

use App\Models\CommandAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class DestructiveCommandExecuted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CommandAuditLog $log,
        public int $exitCode
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('audit.notifications.mail_enabled')) {
            $channels[] = 'mail';
        }

        if (config('audit.notifications.slack_enabled')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->log->user?->name ?? 'System/CLI';
        $status = $this->exitCode === 0 ? 'successfully executed' : 'failed';
        $statusColor = $this->exitCode === 0 ? 'warning' : 'error';

        return (new MailMessage)
            ->level($statusColor)
            ->subject("⚠️ Destructive Command Executed: {$this->log->command_name}")
            ->greeting("Destructive Command Alert")
            ->line("A destructive command was {$status}:")
            ->line("**Command:** `{$this->log->command_signature}`")
            ->line("**User:** {$userName}")
            ->line("**Status:** ".($this->exitCode === 0 ? '✅ Success' : '❌ Failed'))
            ->line("**Exit Code:** {$this->exitCode}")
            ->line("**Execution Time:** {$this->log->execution_time_ms}ms")
            ->line("**IP Address:** {$this->log->ip_address}")
            ->line("**Timestamp:** {$this->log->created_at->format('Y-m-d H:i:s')}")
            ->when($this->exitCode !== 0, function ($message) {
                return $message->line("**Error Output:** ```{$this->log->error_output}```");
            })
            ->action('View Audit Logs', url('/admin/command-audit-logs'));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $userName = $this->log->user?->name ?? 'System/CLI';
        $emoji = $this->exitCode === 0 ? ':warning:' : ':x:';

        return (new SlackMessage)
            ->from('Audit System', ':shield:')
            ->to(config('audit.notifications.slack_channel', '#alerts'))
            ->content("{$emoji} Destructive Command Executed")
            ->attachment(function ($attachment) use ($userName) {
                $attachment
                    ->title('Command Details')
                    ->fields([
                        'Command' => $this->log->command_signature,
                        'User' => $userName,
                        'Status' => $this->exitCode === 0 ? 'Success' : 'Failed',
                        'Exit Code' => $this->exitCode,
                        'Execution Time' => "{$this->log->execution_time_ms}ms",
                        'IP Address' => $this->log->ip_address,
                        'Timestamp' => $this->log->created_at->format('Y-m-d H:i:s'),
                    ])
                    ->color($this->exitCode === 0 ? 'warning' : 'danger');
            });
    }

    public function toArray(object $notifiable): array
    {
        return [
            'command' => $this->log->command_name,
            'signature' => $this->log->command_signature,
            'user' => $this->log->user?->name ?? 'System/CLI',
            'exit_code' => $this->exitCode,
            'execution_time_ms' => $this->log->execution_time_ms,
            'ip_address' => $this->log->ip_address,
            'timestamp' => $this->log->created_at,
        ];
    }
}
