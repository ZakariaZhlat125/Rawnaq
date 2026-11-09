<?php

namespace App\Services;

use Modules\BussinessHour\Models\Shift;
use Modules\Employee\Models\BranchEmployee;

class EmployeeShiftSchedule
{
    public static function select(?int $base, array $active, string $date, bool $rotate, string $anchor): ?int
    {
        if (!$base || !in_array($base, $active, true)) return null;
        if (!$rotate || count($active) !== 2) return $base;
        $zone = new \DateTimeZone('Asia/Riyadh');
        $weekStart = function ($value) use ($zone) {
            $day = new \DateTimeImmutable($value, $zone);
            return $day->modify('-'.$day->format('w').' days')->setTime(0, 0);
        };
        $weeks = intdiv(abs((int) $weekStart($anchor)->diff($weekStart($date))->format('%r%a')), 7);
        return $weeks % 2 === 0 ? $base : array_values(array_filter($active, fn ($id) => $id !== $base))[0];
    }

    public function resolve($base, string $date, ?int $employeeId): ?int
    {
        $assignment = $employeeId ? BranchEmployee::where('employee_id', $employeeId)->first() : null;
        $active = Shift::where('status', 1)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        return self::select($base ? (int) $base : null, $active, $date, (bool) ($assignment->shift_rotation_enabled ?? true), $assignment->shift_rotation_anchor ?? '2026-02-15');
    }
}
