<?php

namespace App\Services\Scheduler;

use Carbon\CarbonImmutable;
use DateTimeZone;

class NextRunCalculator
{
    public function forDailyAt(string $hhmm, string $tz): self
    {
        $this->hhmm = $hhmm;
        $this->tz = $tz;
        return $this;
    }

    public function firstUtcAfter(CarbonImmutable $utcNow): string
    {
        [$h,$m] = explode(':', $this->hhmm);
        $localNow = $utcNow->setTimezone(new DateTimeZone($this->tz));
        $candidate = $localNow->setTime((int)$h,(int)$m,0);
        if ($candidate->lessThanOrEqualTo($localNow)) {
            $candidate = $candidate->addDay();
        }
        return $candidate->setTimezone('UTC')->toIso8601String();
    }
}
