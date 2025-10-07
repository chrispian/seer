# Production Setup for Orchestration Layer

**Created**: 2025-10-07  
**Priority**: High  
**Category**: DevOps / Production  
**Component**: Orchestration (Postmaster, Artifacts, Memory)  
**Estimated Effort**: 3-4 hours  
**Assigned To**: @chrispian  
**Related Sprint**: SPRINT-51

---

## Problem Statement

The orchestration layer (Postmaster, Artifacts Store, Memory Service) has been fully implemented and tested in SPRINT-51, but requires production infrastructure setup before it can be deployed.

Three critical production components need to be configured:
1. **Queue Worker** - Supervisor configuration for `postmaster:run` daemon
2. **Storage Monitoring** - Disk usage alerts for content-addressable storage
3. **Memory Compaction** - Cron job for 24-hour TTL cleanup

---

## Prerequisites

- ✅ SPRINT-51 complete (all code merged to main)
- ✅ Database migrations run (`php artisan migrate`)
- ✅ All 31 tests passing
- ✅ Documentation complete (`docs/orchestration/postmaster-and-init.md`)

---

## Task Breakdown

### 1. Supervisor Configuration for Postmaster Queue Worker

**File**: `/etc/supervisor/conf.d/seer-postmaster.conf`

**Objective**: Run `php artisan postmaster:run` as a supervised daemon that auto-restarts on failure.

**Requirements**:
- Process name: `seer-postmaster`
- Command: `php artisan postmaster:run`
- Auto-restart: Yes
- User: Web server user (e.g., `www-data` or `forge`)
- Log files: `/var/log/supervisor/seer-postmaster.log`
- Stderr redirect: Yes

**Acceptance Criteria**:
- [ ] Supervisor config file created
- [ ] Process starts successfully with `supervisorctl start seer-postmaster`
- [ ] Process auto-restarts after manual kill
- [ ] Logs are written to `/var/log/supervisor/`
- [ ] Queue processes jobs from `postmaster` queue

**Testing**:
```bash
# Start the worker
sudo supervisorctl start seer-postmaster

# Send test parcel
php artisan tinker
>>> ProcessParcel::dispatch(['test' => true], 'task-uuid');

# Verify processing in logs
tail -f /var/log/supervisor/seer-postmaster.log
```

---

### 2. Storage Monitoring for CAS (Content-Addressable Storage)

**Directory**: `storage/orchestration/cas/`

**Objective**: Monitor disk usage and alert when CAS storage exceeds thresholds.

**Requirements**:
- Monitor path: `storage/orchestration/cas/`
- Alert at: 80% of allocated quota (or 10GB, whichever is lower)
- Critical at: 90% of allocated quota
- Metrics: Total size, file count, growth rate
- Notification: Email/Slack to ops team

**Acceptance Criteria**:
- [ ] Monitoring script created (bash or Laravel command)
- [ ] Cron job configured (runs daily)
- [ ] Alert thresholds configurable via `.env`
- [ ] Test alert sends successfully
- [ ] Dashboard widget (optional) showing CAS usage

**Implementation Options**:

**Option A: Laravel Command**
```bash
# Create command
php artisan make:command Orchestration/MonitorCasStorage

# Add to cron
* * * * * php artisan schedule:run
```

**Option B: Bash Script + Cron**
```bash
#!/bin/bash
# /usr/local/bin/monitor-cas-storage.sh
STORAGE_PATH="/path/to/storage/orchestration/cas"
SIZE=$(du -sb "$STORAGE_PATH" | cut -f1)
THRESHOLD=10737418240  # 10GB in bytes

if [ "$SIZE" -gt "$THRESHOLD" ]; then
  echo "CAS storage exceeds threshold: $(($SIZE / 1024 / 1024)) MB"
  # Send alert
fi
```

**Testing**:
```bash
# Run monitoring script manually
php artisan orchestration:monitor-storage --dry-run

# Verify alert logic
php artisan orchestration:monitor-storage --simulate-high-usage
```

---

### 3. Memory Compaction Cron Job

**Service**: `App\Services\Orchestration\Memory\MemoryService`

**Objective**: Automatically compact ephemeral memory for agents after 24-hour TTL.

**Requirements**:
- Frequency: Every 6 hours (or daily at off-peak)
- Operation: Call `MemoryService::compact()` for agents
- TTL: 24 hours (configurable in `config/orchestration.php`)
- Logging: Record compaction stats (keys deleted, agents processed)

**Acceptance Criteria**:
- [ ] Laravel command created: `php artisan orchestration:compact-memory`
- [ ] Command added to `app/Console/Kernel.php` schedule
- [ ] Compaction runs automatically via `schedule:run`
- [ ] Logs written to `storage/logs/orchestration.log`
- [ ] Test compaction with manual execution

**Implementation**:

**Step 1: Create Command**
```bash
php artisan make:command Orchestration/CompactMemory
```

**Step 2: Add to Kernel**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('orchestration:compact-memory')
        ->dailyAt('02:00')  // 2 AM daily
        ->appendOutputTo(storage_path('logs/orchestration.log'));
}
```

**Step 3: Command Logic**
```php
public function handle(MemoryService $memoryService)
{
    $agents = AgentProfile::all();
    $totalCompacted = 0;

    foreach ($agents as $agent) {
        $compacted = $memoryService->compact($agent->id);
        $totalCompacted += $compacted;
        $this->info("Compacted {$compacted} keys for agent {$agent->id}");
    }

    $this->info("Total: {$totalCompacted} ephemeral keys removed");
}
```

**Testing**:
```bash
# Run manually
php artisan orchestration:compact-memory

# Verify via tinker
php artisan tinker
>>> use App\Services\Orchestration\Memory\MemoryService;
>>> $memory = app(MemoryService::class);
>>> $memory->setEphemeral('test-agent', 'temp-key', 'value');
>>> $memory->get('test-agent', 'temp-key');  // Should return 'value'
>>> $memory->compact('test-agent');
>>> $memory->get('test-agent', 'temp-key');  // Should return null
```

---

## Environment Variables

Add to `.env`:

```bash
# Orchestration Configuration
ORCHESTRATION_REDACT_SECRETS=true
ORCHESTRATION_CAS_MAX_SIZE=107374182400  # 100GB
ORCHESTRATION_CAS_ALERT_THRESHOLD=0.8    # 80%
ORCHESTRATION_MEMORY_TTL_HOURS=24

# Queue Configuration
QUEUE_CONNECTION=redis  # or 'database' for simple setups
POSTMASTER_QUEUE=postmaster
```

---

## Deployment Checklist

### Pre-Deployment
- [ ] Review `config/orchestration.php` settings
- [ ] Verify `storage/orchestration/` directory exists with correct permissions
- [ ] Run `php artisan migrate` (messages + orchestration_artifacts tables)
- [ ] Run all tests: `composer test -- --filter=Orchestration`

### Deployment Steps
1. [ ] Deploy code to production
2. [ ] Run migrations: `php artisan migrate --force`
3. [ ] Create storage directories:
   ```bash
   mkdir -p storage/orchestration/{cas,memory}
   chmod -R 775 storage/orchestration
   chown -R www-data:www-data storage/orchestration
   ```
4. [ ] Install Supervisor config: `sudo cp seer-postmaster.conf /etc/supervisor/conf.d/`
5. [ ] Reload Supervisor: `sudo supervisorctl reread && sudo supervisorctl update`
6. [ ] Start Postmaster: `sudo supervisorctl start seer-postmaster`
7. [ ] Configure cron for Laravel scheduler:
   ```bash
   * * * * * cd /path/to/seer && php artisan schedule:run >> /dev/null 2>&1
   ```
8. [ ] Test with sample parcel (see "Testing" section)

### Post-Deployment
- [ ] Verify Postmaster queue worker is running: `sudo supervisorctl status seer-postmaster`
- [ ] Check logs: `tail -f /var/log/supervisor/seer-postmaster.log`
- [ ] Send test message via MessagingAPI
- [ ] Verify storage monitoring runs: `php artisan orchestration:monitor-storage`
- [ ] Confirm memory compaction scheduled: `php artisan schedule:list`

---

## Rollback Plan

If issues arise:

1. **Stop Postmaster Worker**:
   ```bash
   sudo supervisorctl stop seer-postmaster
   ```

2. **Pause Queue Processing**:
   ```bash
   php artisan queue:pause postmaster
   ```

3. **Revert Code**:
   ```bash
   git revert <commit-hash>
   php artisan migrate:rollback --step=2
   ```

4. **Restore Supervisor**:
   ```bash
   sudo supervisorctl remove seer-postmaster
   sudo rm /etc/supervisor/conf.d/seer-postmaster.conf
   ```

---

## Monitoring & Alerts

### Key Metrics to Track

| Metric | Threshold | Alert Level |
|--------|-----------|-------------|
| CAS storage size | >10GB or >80% | Warning |
| CAS storage size | >90% | Critical |
| Postmaster queue depth | >100 jobs | Warning |
| Postmaster queue depth | >500 jobs | Critical |
| Failed job count | >10/hour | Warning |
| Memory compaction failures | >3/day | Critical |

### Recommended Tools
- **Laravel Horizon** (if using Redis queues)
- **Prometheus + Grafana** (for custom metrics)
- **New Relic / DataDog** (for APM)

---

## Documentation References

- **Architecture**: `docs/orchestration/postmaster-and-init.md`
- **API Reference**: `docs/orchestration/postmaster-and-init.md#components-reference`
- **Sprint Summary**: `delegation/sprints/SPRINT-51-SUMMARY.md`
- **Test Coverage**: `tests/Feature/Orchestration/` and `tests/Unit/Services/Orchestration/`

---

## Questions for @chrispian

1. **Queue Backend**: Redis or database queue? (Redis recommended for production)
2. **Storage Limit**: What's the max CAS storage allocation? (Default: 100GB)
3. **Alert Destination**: Email/Slack webhook for monitoring alerts?
4. **Server Environment**: Forge, Vapor, custom server?
5. **Monitoring Tools**: Existing APM/monitoring stack to integrate with?

---

## Success Criteria

✅ **Complete when**:
- Supervisor manages Postmaster worker (auto-restart on failure)
- Storage monitoring runs daily and alerts at thresholds
- Memory compaction runs automatically every 24 hours
- All components tested in production environment
- Documentation updated with production config

---

## Related Tasks

- **Prerequisite**: SPRINT-51 (✅ Complete)
- **Follow-up**: PM Integration (send parcels via ProcessParcel)
- **Follow-up**: Agent Workflow (consume messages via MCP tools)

---

## Notes

- This task is purely DevOps/infrastructure - no code changes required
- All application code was completed in SPRINT-51
- Estimated 3-4 hours assumes familiarity with Supervisor and cron
- Can be done incrementally (Supervisor first, then monitoring, then compaction)

---

**Status**: 🟡 Ready to Start  
**Blocked By**: None  
**Blocks**: PM Integration, Agent Workflows
