<?php
require_once __DIR__ . '/../../app/Services/HomeService.php';
use App\Services\HomeService;
$center = [21.5433, 39.1728];
$checks = [
    'center is covered' => HomeService::distance(...[...$center, ...$center]) === 0.0,
    'nearby location is covered' => HomeService::distance(21.55, 39.18, ...$center) < 5,
    'distant location is excluded' => HomeService::distance(30.0444, 31.2357, ...$center) > 50,
    'antipodal distance is finite' => is_finite(HomeService::distance(0, 0, 0, 180)),
];
foreach ($checks as $label => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: $label\n"); exit(1); }
}
echo "Home service coverage: 4 checks passed.\n";
