# Audit Logging System

## Overview
Comprehensive audit logging system that tracks all database changes (model events) and artisan command executions. Built using a hybrid approach: Spatie Activity Log for model auditing + custom command logging infrastructure.

## Features
- ✅ Automatic model event logging (created/updated/deleted)
- ✅ User attribution for all changes
- ✅ Before/after state tracking
- ✅ Artisan command execution logging
- ✅ Destructive command detection and alerting
- ✅ Multi-channel notifications (database/mail/slack)
- ✅ 90-day retention policy with automated cleanup
- ✅ IP address and session tracking

## Architecture

### Two-Table System
1. **`activity_log`** (Spatie) - Model event auditing
2. **`command_audit_logs`** (Custom) - Artisan command tracking

### Components
- **Models:** `User`, `Fragment` (with `LogsActivity` trait), `CommandAuditLog`
- **Listeners:** `CommandLoggingListener` (hooks `CommandStarting`/`CommandFinished`)
- **Notifications:** `DestructiveCommandExecuted`
- **Providers:** `AuditServiceProvider`
- **Commands:** `CleanupAuditLogs`

## Usage

### Model Auditing (Automatic)
Models with the `LogsActivity` trait automatically log changes:

```php
// User model changes are automatically logged
$user = User::find(1);
$user->name = 'New Name';
$user->save(); // Logged automatically

// Query the logs
$activities = Activity::where('subject_type', User::class)
    ->where('subject_id', 1)
    ->get();
```

### Querying Activity Logs

```php
use Spatie\Activitylog\Models\Activity;

// Get all activities
$all = Activity::all();

// Get activities for a specific model
$userActivities = Activity::where('subject_type', User::class)
    ->where('subject_id', 1)
    ->latest()
    ->get();

// Get activities by user
$adminActivities = Activity::where('causer_type', User::class)
    ->where('causer_id', 1)
    ->get();

// Get destructive command activities
$destructive = Activity::where('event', 'destructive_command')
    ->latest()
    ->get();

// Access properties
$activity = Activity::first();
echo $activity->description; // 'updated'
echo $activity->changes; // ['attributes' => [...], 'old' => [...]]
echo $activity->causer->name; // User who made change
```

### Querying Command Logs

```php
use App\Models\CommandAuditLog;

// Get recent commands
$recent = CommandAuditLog::orderBy('created_at', 'desc')
    ->limit(50)
    ->get();

// Get destructive commands
$destructive = CommandAuditLog::destructive()
    ->recent(7) // Last 7 days
    ->get();

// Get failed commands
$failed = CommandAuditLog::failed()->get();

// Get specific command executions
$migrations = CommandAuditLog::byCommand('migrate')->get();

// With user relationship
$logs = CommandAuditLog::with('user')
    ->where('is_destructive', true)
    ->get();
```

### Command Logging (Automatic)
All artisan commands are automatically logged. No action required.

```bash
# These commands are all logged automatically
php artisan migrate
php artisan cache:clear  # Marked as destructive
php artisan custom:command
```

### Destructive Commands
14 command patterns are flagged as destructive:
- `migrate:fresh`, `migrate:reset`, `migrate:rollback`
- `db:wipe`, `db:seed`
- `cache:clear`, `config:clear`, `route:clear`, `view:clear`
- `queue:flush`, `queue:clear`
- `telescope:prune`, `horizon:purge`

When executed, these commands:
1. Are marked with `is_destructive = true` in `command_audit_logs`
2. Create an activity log entry with `event = 'destructive_command'`
3. Send notifications to configured channels (database/mail/slack)

## Configuration

### Environment Variables
```bash
# config/audit.php
AUDIT_NOTIFICATIONS_ENABLED=true
AUDIT_MAIL_ENABLED=false
AUDIT_SLACK_ENABLED=false
AUDIT_SLACK_CHANNEL=#alerts
AUDIT_ADMIN_EMAIL=admin@example.com
AUDIT_RETENTION_DAYS=90
```

### Customizing Destructive Commands
Edit `config/audit.php`:

```php
'destructive_commands' => [
    'migrate:fresh',
    'db:wipe',
    'your:custom:command', // Add custom patterns
],
```

### Configuring Model Logging
Add trait and configure options:

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class YourModel extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['field1', 'field2', 'field3']) // Specific fields
            ->logOnlyDirty() // Only log changed values
            ->dontSubmitEmptyLogs() // Skip if no changes
            ->dontLogIfAttributesChangedOnly(['updated_at']) // Ignore timestamp changes
            ->useLogName('your_model'); // Custom log name
    }
}
```

## Notifications

### Email Notifications
Enable in `.env`:
```bash
AUDIT_MAIL_ENABLED=true
AUDIT_ADMIN_EMAIL=admin@example.com
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@example.com
```

Email includes:
- Command name and signature
- User who executed (or "System/CLI")
- Status (success/failed)
- Exit code and execution time
- IP address
- Error output (if failed)

### Slack Notifications
Enable in `.env`:
```bash
AUDIT_SLACK_ENABLED=true
AUDIT_SLACK_CHANNEL=#alerts
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

### Database Notifications
Always enabled. Query via:
```php
$user = User::find(1);
$notifications = $user->notifications()
    ->where('type', DestructiveCommandExecuted::class)
    ->get();
```

## Cleanup & Retention

### Manual Cleanup
```bash
# Dry run - see what would be deleted
php artisan audit:cleanup --dry-run

# Clean up logs older than 90 days (default)
php artisan audit:cleanup

# Custom retention period
php artisan audit:cleanup --days=30

# Non-interactive mode
php artisan audit:cleanup --no-interaction
```

### Automated Cleanup (Recommended)
Add to Laravel scheduler in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('audit:cleanup --no-interaction')
    ->weekly()
    ->sundays()
    ->at('02:00');
```

## Database Schema

### activity_log (Spatie)
```sql
- id
- log_name (varchar)
- description (text)
- subject_type (varchar) - Model class
- subject_id (bigint) - Model ID
- causer_type (varchar) - User class
- causer_id (bigint) - User ID
- event (varchar) - created/updated/deleted/destructive_command
- properties (json) - Changes, attributes, old values
- batch_uuid (uuid) - Group related activities
- created_at, updated_at
```

### command_audit_logs (Custom)
```sql
- id
- command_name (varchar)
- command_signature (text) - Full command with args
- arguments (json) - Sanitized arguments
- options (json) - Sanitized options
- status (varchar) - pending/running/completed/failed
- exit_code (int)
- output (text) - Command output
- error_output (text) - Error output
- execution_time_ms (int)
- user_id (bigint nullable) - Authenticated user
- ip_address (varchar)
- user_agent (text)
- session_id (varchar)
- is_destructive (boolean)
- started_at (timestamp)
- completed_at (timestamp)
- created_at, updated_at
```

## Performance Considerations

### Indexes
Both tables have proper indexes:
- `activity_log`: subject, causer, event, created_at, batch_uuid
- `command_audit_logs`: command_name, is_destructive, user_id, status, created_at

### Async Logging
Command logging is synchronous but lightweight (~5ms overhead).
Model logging uses Eloquent events (synchronous).

For high-throughput applications, consider:
1. Queue notifications: `DestructiveCommandExecuted` implements `ShouldQueue`
2. Batch cleanup during off-peak hours
3. Archive old logs to separate table/database

### Storage Growth
Estimate: ~1KB per activity, ~2KB per command log

**Example calculation:**
- 1,000 commands/day = 2MB/day = 60MB/month
- 10,000 model changes/day = 10MB/day = 300MB/month
- 90-day retention ≈ 30GB worst case

Use `audit:cleanup` to manage growth.

## Security

### Sensitive Data Sanitization
Command logging automatically redacts:
- Arguments containing: password, secret, token
- Options with keys: password, secret, token, key

Custom sanitization in `CommandLoggingListener::sanitizeArguments()`.

### Access Control
Audit logs should only be accessible to administrators.

**Recommended:**
- Add middleware to audit log routes
- Use Laravel policies for `Activity` and `CommandAuditLog` models
- Implement UI with admin-only access (see TASK-0003)

### Tamper-Proofing
Both tables are append-only by design. Consider:
- Database-level triggers to prevent updates/deletes
- Separate database user with INSERT-only permissions
- Regular backups with versioning

## Troubleshooting

### Logs not appearing
```bash
# Check provider is registered
php artisan about

# Check listener is registered
php artisan event:list | grep Command

# Check config is cached
php artisan config:clear

# Verify migrations ran
php artisan migrate:status
```

### High storage usage
```bash
# Check log counts
php artisan tinker
>>> Activity::count()
>>> CommandAuditLog::count()

# Run cleanup
php artisan audit:cleanup --days=30
```

### Missing user attribution
```php
// Manually set causer
activity()
    ->causedBy($user)
    ->performedOn($model)
    ->log('Custom event');
```

## Testing

### Test Model Logging
```php
$user = User::factory()->create(['name' => 'Test']);
$user->update(['name' => 'Updated']);

$activity = Activity::latest()->first();
$this->assertEquals('updated', $activity->description);
$this->assertEquals('Test', $activity->changes['old']['name']);
$this->assertEquals('Updated', $activity->changes['attributes']['name']);
```

### Test Command Logging
```bash
php artisan cache:clear

# Check log
php artisan tinker
>>> CommandAuditLog::latest()->first()
```

## Related Documentation
- Spatie Activity Log: https://spatie.be/docs/laravel-activitylog/
- Laravel Events: https://laravel.com/docs/events
- Laravel Notifications: https://laravel.com/docs/notifications

## Future Enhancements
See `delegation/backlog/database-audit-logs/TASK-0003.md` for planned React UI.

## Support
For issues or questions, check:
1. This documentation
2. `delegation/backlog/database-audit-logs/TASK-0002.md` (implementation details)
3. Codebase: `app/Listeners/CommandLoggingListener.php`
