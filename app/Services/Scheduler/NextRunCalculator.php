<?php

namespace App\Services\Scheduler;

use Carbon\Carbon;

class NextRunCalculator
{
    /**
     * Calculate the next run time for a schedule
     */
    public function calculateNextRun(
        string $recurrenceType,
        ?string $recurrenceValue,
        string $timezone,
        ?\DateTime $currentRunTime = null
    ): ?\DateTime {
        $now = $currentRunTime ? Carbon::instance($currentRunTime) : Carbon::now();
        $localNow = $now->setTimezone($timezone);

        switch ($recurrenceType) {
            case 'one_off':
                return null; // One-off schedules don't have a next run

            case 'daily_at':
                return $this->calculateDailyAt($recurrenceValue, $timezone, $localNow);

            case 'weekly_at':
                return $this->calculateWeeklyAt($recurrenceValue, $timezone, $localNow);

            case 'cron_expr':
                return $this->calculateCronExpression($recurrenceValue, $timezone, $localNow);

            default:
                throw new \InvalidArgumentException("Unknown recurrence type: {$recurrenceType}");
        }
    }

    /**
     * Calculate next daily run at specific time
     */
    protected function calculateDailyAt(string $timeValue, string $timezone, Carbon $localNow): \DateTime
    {
        [$hour, $minute] = $this->parseTime($timeValue);
        
        $nextRun = $localNow->copy()
            ->setTime($hour, $minute, 0);

        // If the time has already passed today, move to tomorrow
        if ($nextRun->lte($localNow)) {
            $nextRun->addDay();
        }

        return $nextRun->utc()->toDateTime();
    }

    /**
     * Calculate next weekly run at specific days and time
     */
    protected function calculateWeeklyAt(string $scheduleValue, string $timezone, Carbon $localNow): \DateTime
    {
        // Parse format like "MON,WED,FRI:09:00" or "MON:09:00"
        if (str_contains($scheduleValue, ':')) {
            [$daysStr, $timeStr] = explode(':', $scheduleValue, 2);
            $days = explode(',', $daysStr);
            [$hour, $minute] = $this->parseTime($timeStr);
        } else {
            // Default to Monday at 09:00 if no time specified
            $days = explode(',', $scheduleValue);
            $hour = 9;
            $minute = 0;
        }

        $dayMap = [
            'MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4,
            'FRI' => 5, 'SAT' => 6, 'SUN' => 0
        ];

        $targetDays = array_map(fn($day) => $dayMap[trim($day)] ?? 1, $days);
        sort($targetDays);

        $currentDayOfWeek = (int) $localNow->format('w'); // 0 = Sunday, 6 = Saturday
        $currentTime = $localNow->format('H:i');
        $targetTime = sprintf('%02d:%02d', $hour, $minute);

        // Find the next occurrence
        foreach ($targetDays as $targetDay) {
            $nextRun = $localNow->copy()->setTime($hour, $minute, 0);
            
            if ($targetDay > $currentDayOfWeek || 
                ($targetDay == $currentDayOfWeek && $targetTime > $currentTime)) {
                // Same week
                $daysToAdd = $targetDay - $currentDayOfWeek;
                $nextRun->addDays($daysToAdd);
                return $nextRun->utc()->toDateTime();
            }
        }

        // Next week - use first day
        $daysToAdd = 7 - $currentDayOfWeek + $targetDays[0];
        $nextRun = $localNow->copy()
            ->setTime($hour, $minute, 0)
            ->addDays($daysToAdd);

        return $nextRun->utc()->toDateTime();
    }

    /**
     * Calculate next run based on cron expression
     */
    protected function calculateCronExpression(string $cronExpr, string $timezone, Carbon $localNow): \DateTime
    {
        // Basic cron implementation for common patterns
        // Format: minute hour day month day_of_week
        $parts = explode(' ', trim($cronExpr));
        
        if (count($parts) !== 5) {
            throw new \InvalidArgumentException("Invalid cron expression: {$cronExpr}. Expected 5 parts.");
        }
        
        [$minute, $hour, $day, $month, $dayOfWeek] = $parts;
        
        // Start from next minute to avoid immediate re-execution
        $nextRun = $localNow->copy()->addMinute()->second(0);
        
        // Find next matching time (simple brute force approach for MVP)
        for ($i = 0; $i < 366 * 24 * 60; $i++) { // Search up to 1 year ahead
            if ($this->cronMatches($nextRun, $minute, $hour, $day, $month, $dayOfWeek)) {
                return $nextRun->utc()->toDateTime();
            }
            $nextRun->addMinute();
        }
        
        throw new \RuntimeException("Could not find next run time for cron expression: {$cronExpr}");
    }
    
    /**
     * Check if current time matches cron expression
     */
    protected function cronMatches(Carbon $time, string $minute, string $hour, string $day, string $month, string $dayOfWeek): bool
    {
        // Check minute
        if (!$this->cronFieldMatches($time->minute, $minute, 0, 59)) {
            return false;
        }
        
        // Check hour
        if (!$this->cronFieldMatches($time->hour, $hour, 0, 23)) {
            return false;
        }
        
        // Check month
        if (!$this->cronFieldMatches($time->month, $month, 1, 12)) {
            return false;
        }
        
        // Day and day_of_week are special - if both are specified, either can match
        $dayMatches = $this->cronFieldMatches($time->day, $day, 1, 31);
        $dayOfWeekMatches = $this->cronFieldMatches($time->dayOfWeek, $dayOfWeek, 0, 6); // 0 = Sunday
        
        // If both day and dayOfWeek are wildcards, both match
        if ($day === '*' && $dayOfWeek === '*') {
            return true;
        }
        
        // If only one is wildcard, use the other
        if ($day === '*') {
            return $dayOfWeekMatches;
        }
        if ($dayOfWeek === '*') {
            return $dayMatches;
        }
        
        // If both are specified, either can match
        return $dayMatches || $dayOfWeekMatches;
    }
    
    /**
     * Check if a field value matches cron field pattern
     */
    protected function cronFieldMatches(int $value, string $pattern, int $min, int $max): bool
    {
        // Wildcard
        if ($pattern === '*') {
            return true;
        }
        
        // Single value
        if (is_numeric($pattern)) {
            return $value == (int) $pattern;
        }
        
        // Range (e.g., "1-5")
        if (str_contains($pattern, '-')) {
            [$start, $end] = explode('-', $pattern, 2);
            return $value >= (int) $start && $value <= (int) $end;
        }
        
        // List (e.g., "1,3,5")
        if (str_contains($pattern, ',')) {
            $values = array_map('intval', explode(',', $pattern));
            return in_array($value, $values);
        }
        
        // Step values (e.g., "*/5" or "2-10/2")
        if (str_contains($pattern, '/')) {
            [$range, $step] = explode('/', $pattern, 2);
            $stepValue = (int) $step;
            
            if ($range === '*') {
                return ($value - $min) % $stepValue === 0;
            }
            
            if (str_contains($range, '-')) {
                [$start, $end] = explode('-', $range, 2);
                $startValue = (int) $start;
                $endValue = (int) $end;
                return $value >= $startValue && $value <= $endValue && ($value - $startValue) % $stepValue === 0;
            }
        }
        
        return false;
    }

    /**
     * Parse time string like "07:30" or "7:30"
     */
    protected function parseTime(string $timeStr): array
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $timeStr, $matches)) {
            throw new \InvalidArgumentException("Invalid time format: {$timeStr}. Expected HH:MM");
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException("Invalid time values: {$timeStr}");
        }

        return [$hour, $minute];
    }

    /**
     * Create a one-off schedule for a specific date/time
     */
    public function createOneOffSchedule(string $runAtLocal, string $timezone): \DateTime
    {
        $localTime = Carbon::parse($runAtLocal, $timezone);
        return $localTime->utc()->toDateTime();
    }
}