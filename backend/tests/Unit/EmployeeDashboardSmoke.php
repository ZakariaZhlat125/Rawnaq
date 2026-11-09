<?php

require_once __DIR__.'/../../app/Services/EmployeeShiftSchedule.php';
require_once __DIR__.'/../../app/Services/EmployeeReviewSummary.php';
require_once __DIR__.'/../../app/Services/EmployeePerformanceReport.php';

use App\Services\EmployeeShiftSchedule as Schedule;
use App\Services\EmployeeReviewSummary as Reviews;
use App\Services\EmployeePerformanceReport as Performance;

$checks = 0;
function check($expected, $actual, string $label): void {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException($label.': '.var_export($actual, true));
    $checks++;
}

check(1, Schedule::select(1, [1,2], '2026-02-15', true, '2026-02-15'), 'Anchor week');
check(1, Schedule::select(1, [1,2], '2026-02-21', true, '2026-02-15'), 'Saturday stays in anchor week');
check(2, Schedule::select(1, [1,2], '2026-02-22', true, '2026-02-15'), 'Sunday rotates');
check(1, Schedule::select(1, [1,2], '2026-03-01', true, '2026-02-15'), 'Two-week cycle');
check(2, Schedule::select(1, [1,2], '2026-02-08', true, '2026-02-15'), 'Weeks before anchor');
check(1, Schedule::select(1, [1,2], '2026-02-22', false, '2026-02-15'), 'Fixed shift');
check(1, Schedule::select(1, [1,2,3], '2026-02-22', true, '2026-02-15'), 'Three shifts use assigned shift');
check(null, Schedule::select(1, [2], '2026-02-22', true, '2026-02-15'), 'Inactive assigned shift has no availability');
check(null, Schedule::select(null, [1,2], '2026-02-22', true, '2026-02-15'), 'Unassigned has no availability');
check(2, Schedule::select(2, [1,2], '2026-10-17', true, '2026-10-17'), 'Custom anchor uses chosen shift');

$votes = Reviews::merge([
    ['booking_id'=>10, 'user_id'=>20, 'service_ratings'=>[
        ['employee_id'=>1, 'employee_rating'=>5], ['employee_id'=>1, 'employee_rating'=>3],
        ['employee_id'=>2, 'employee_rating'=>2], ['employee_id'=>null, 'employee_rating'=>null],
    ]],
], [
    ['id'=>1, 'booking_id'=>10, 'user_id'=>20, 'employee_id'=>1, 'rating'=>1],
    ['id'=>2, 'booking_id'=>11, 'user_id'=>20, 'employee_id'=>1, 'rating'=>5],
]);
check(3, count($votes), 'Modern and legacy votes are not counted twice');
check(4.0, $votes[0]['rating'], 'Multiple services average into one customer vote');
check(2.0, $votes[1]['rating'], 'Other employee has independent rating');
check(5.0, $votes[2]['rating'], 'Legacy-only review remains visible');
check(30.0, Performance::allocate(300, 100, 1000), 'Partial collection allocated across mixed checkout');
check(0.0, Performance::allocate(0, 100, 1000), 'Uncollected invoice is not revenue');
check(0.0, Performance::allocate(100, 100, 0), 'Zero checkout base');
check(100.0, Performance::allocate(100, 200, 100), 'Never allocate more than collected');
echo "Employee dashboard: $checks checks passed.\n";
