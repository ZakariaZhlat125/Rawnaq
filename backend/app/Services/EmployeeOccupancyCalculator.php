<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class EmployeeOccupancyCalculator
{
    /** Half-open timestamp intervals avoid double-counting adjacent or overlapping work. */
    public function merge(array $ranges): array
    {
        $ranges = array_values(array_filter($ranges, fn ($range) => $range[1] > $range[0]));
        usort($ranges, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($ranges as [$start, $end]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }
        return $merged;
    }

    public function subtract(array $ranges, array $excluded): array
    {
        $ranges = $this->merge($ranges);
        foreach ($this->merge($excluded) as [$cutStart, $cutEnd]) {
            $next = [];
            foreach ($ranges as [$start, $end]) {
                if ($cutEnd <= $start || $cutStart >= $end) {
                    $next[] = [$start, $end];
                    continue;
                }
                if ($start < $cutStart) $next[] = [$start, $cutStart];
                if ($end > $cutEnd) $next[] = [$cutEnd, $end];
            }
            $ranges = $next;
        }
        return $ranges;
    }

    public function workingRanges(string $date, array $config, string $timezone): array
    {
        if (!empty($config['is_holiday']) || empty($config['start_time']) || empty($config['end_time'])) return [];
        try {
            $start = CarbonImmutable::parse($date.' '.$config['start_time'], $timezone);
            $end = CarbonImmutable::parse($date.' '.$config['end_time'], $timezone);
            if ($end->equalTo($start)) return [];
            if ($end->lessThan($start)) $end = $end->addDay();
            $breaks = $config['breaks'] ?? [];
            if (is_string($breaks)) $breaks = json_decode($breaks, true);
            $excluded = [];
            foreach (is_array($breaks) ? $breaks : [] as $break) {
                $from = $break['start_break'] ?? $break['start'] ?? null;
                $to = $break['end_break'] ?? $break['end'] ?? null;
                if (!$from || !$to) continue;
                $breakStart = CarbonImmutable::parse($date.' '.$from, $timezone);
                $breakEnd = CarbonImmutable::parse($date.' '.$to, $timezone);
                if ($end->toDateString() !== $date && $breakStart->lessThan($start)) $breakStart = $breakStart->addDay();
                if ($breakEnd->lessThan($breakStart)) $breakEnd = $breakEnd->addDay();
                $excluded[] = [$breakStart->timestamp, $breakEnd->timestamp];
            }
            return $this->subtract([[$start->timestamp, $end->timestamp]], $excluded);
        } catch (\Throwable $exception) {
            // Invalid schedules have no measurable capacity; never invent an eight-hour day.
            return [];
        }
    }

    public function calculate(array $working, array $booked, int $start, int $end): array
    {
        $working = $this->merge(array_map(fn ($range) => [max($range[0], $start), min($range[1], $end)], $working));
        $occupied = [];
        foreach ($this->merge($booked) as [$from, $to]) {
            foreach ($working as [$workStart, $workEnd]) {
                if ($to > $workStart && $from < $workEnd) $occupied[] = [max($from, $workStart), min($to, $workEnd)];
            }
        }
        $seconds = fn ($ranges) => array_sum(array_map(fn ($range) => $range[1] - $range[0], $ranges));
        $available = $seconds($working);
        $busy = $seconds($this->merge($occupied));
        return [
            'available_minutes' => round($available / 60, 2),
            'booked_minutes' => round($busy / 60, 2),
            'percentage' => $available > 0 ? round($busy / $available * 100, 1) : null,
        ];
    }
}
