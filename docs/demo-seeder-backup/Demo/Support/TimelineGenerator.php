<?php

namespace Database\Seeders\Demo\Support;

use Illuminate\Support\Collection;

class TimelineGenerator
{
    public function __construct(private readonly int $days = 90) {}

    public function generate(int $count): Collection
    {
        $start = now()->subDays($this->days - 1)->startOfDay();
        $dates = [];

        for ($i = 0; $i < $count; $i++) {
            $offset = $i * max(1, intdiv($this->days, max(1, $count - 1)));
            $date = $start->copy()->addDays($offset + rand(0, 2));
            $date->setTime(rand(8, 18), rand(0, 59));
            $dates[] = $date;
        }

        return collect($dates)->sort()->values();
    }
}
