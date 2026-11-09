<?php
namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

class QuietHoursRule
{
    public const TIMEZONE = 'Asia/Riyadh';

    /** Booking timestamps and the appointment picker use branch-local time. */
    public function points(array $settings, string $start): int
    {
        if (empty($settings['enabled']) || (int) ($settings['points'] ?? 0) <= 0 || trim($start) === '') return 0;
        try {
            $date = (new DateTimeImmutable($start, new DateTimeZone(self::TIMEZONE)))
                ->setTimezone(new DateTimeZone(self::TIMEZONE));
            $errors = DateTimeImmutable::getLastErrors();
            if ($errors && ($errors['warning_count'] || $errors['error_count'])) return 0;
        } catch (\Exception $e) {
            return 0;
        }
        $hours = $settings['hours'][(int) $date->format('w')] ?? [];
        // [00:00,01:00) belongs to this calendar day, including overnight work.
        return in_array((int) $date->format('G'), array_map('intval', $hours), true)
            ? (int) $settings['points'] : 0;
    }
}
