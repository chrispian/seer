# Creating Schedules — Examples (Artisan/PHP)

## Daily AI News (07:00 America/Chicago)
```php
Schedule::create([
  'id' => Str::uuid(),
  'user_id' => $user->id,
  'workspace_id' => $ws->id,
  'slug' => 'morning-ai-news',
  'command_slug' => 'news-digest-ai',
  'payload' => json_encode(['style'=>'concise','tone'=>'neutral']), // optional
  'schedule_kind' => 'daily_at',
  'tz' => 'America/Chicago',
  'daily_local_time' => '07:00',
  'next_run_at' => NextRunCalculator::forDailyAt('07:00','America/Chicago')->firstUtcAfter(now()),
]);
```

## One-Off: Next Monday 9am “Refill heart meds”
```php
$nextMondayNine = Carbon::now('America/Chicago')->next(Carbon::MONDAY)->setTime(9,0);
Schedule::create([
  'id' => Str::uuid(),
  'user_id' => $user->id,
  'workspace_id' => $ws->id,
  'slug' => 'refill-meds',
  'command_slug' => 'remind',
  'payload' => json_encode(['message'=>'Refill heart meds','due_at_iso'=>$nextMondayNine->toIso8601String()]),
  'schedule_kind' => 'one_off',
  'tz' => 'America/Chicago',
  'run_at_local' => $nextMondayNine,
  'next_run_at' => $nextMondayNine->copy()->setTimezone('UTC'),
  'max_runs' => 1,
]);
```

## Pause / Resume
```php
$s->update(['status'=>'paused']);   // resume: 'active'
```

## Cancel
```php
$s->update(['status':'canceled']);
```
