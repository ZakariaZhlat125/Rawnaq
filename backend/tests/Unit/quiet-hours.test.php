<?php
require __DIR__.'/../../app/Services/QuietHoursRule.php';
$rule = new \App\Services\QuietHoursRule();
$settings = ['enabled' => true, 'points' => 25, 'hours' => [[0, 12, 23], [1], [], [], [], [], []]];
$checks = [
    ['2026-10-25 00:00:00', 25], ['2026-10-25 00:59:59', 25], ['2026-10-25 01:00:00', 0],
    ['2026-10-25 12:00:00', 25], ['2026-10-25 12:59:59', 25], ['2026-10-25 13:00:00', 0],
    ['2026-10-25 23:59:59', 25], ['2026-10-26 00:00:00', 0], ['2026-10-26 01:00:00', 25],
    ['2026-10-27 12:00:00', 0], ['2026-10-25T09:30:00Z', 25], ['invalid', 0], ['', 0],
];
foreach ($checks as [$date, $expected]) {
    if ($rule->points($settings, $date) !== $expected) throw new RuntimeException('Quiet hour mismatch: '.$date);
}
$settings['enabled'] = false;
if ($rule->points($settings, '2026-10-25 12:30:00') !== 0) throw new RuntimeException('Disabled rewards');
echo 'Passed '.(count($checks)+1)." quiet-hour rule checks\n";
