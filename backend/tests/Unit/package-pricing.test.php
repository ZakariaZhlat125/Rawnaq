<?php
// Standalone money-rule tests; no database or Laravel bootstrap required.
require __DIR__.'/../../app/Services/PackagePricing.php';
$pricing = new \App\Services\PackagePricing();
$catalog = [1 => ['price' => 100, 'name' => 'A'], 2 => ['price' => 35.50, 'name' => 'B']];
$row = fn ($id, $qty, $total) => ['service_id' => $id, 'qty' => $qty, 'totalPrice' => $total];
$checks = 0;
$check = function ($condition) use (&$checks) {
    if (!$condition) throw new RuntimeException('Package pricing check failed');
    $checks++;
};
$reject = function ($rows, $reason) use ($pricing, $catalog, $check) {
    try { $pricing->calculate($rows, $catalog); }
    catch (InvalidArgumentException $e) { $check($e->getMessage() === $reason); return; }
    throw new RuntimeException('Invalid package was accepted');
};
$result = $pricing->calculate([$row(1, 2, 150), $row(2, 1, 30)], $catalog);
$check($result['services_total_price'] == 235.50);
$check($result['package_price'] == 180);
$check($result['savings_amount'] == 55.50);
$check($result['services'][0]['discounted_price'] == 75);
$reject([$row(1, 1, 100)], 'price_must_be_lower');
$reject([$row(1, 1, 101)], 'invalid_discount');
$reject([], 'services_required');
$reject([$row(1, 0, 50)], 'invalid_services');
$reject([$row(1, 1.5, 50)], 'invalid_services');
$reject([$row(1, 1, -1)], 'invalid_services');
$reject([$row(1, 1, 0)], 'invalid_services');
$reject([$row(1, 1, 50), $row(1, 1, 50)], 'invalid_services');
$reject([$row(999, 1, 50)], 'invalid_services');
$tampered = $row(1, 1, 100) + ['service_price' => 10000, 'discounted_price' => 1];
$reject([$tampered], 'price_must_be_lower');
$result = $pricing->calculate([$row(2, 3, 106.49)], $catalog);
$check($result['savings_amount'] == 0.01);
$reject([$row(2, 3, 106.499)], 'price_must_be_lower');
echo "Passed {$checks} package pricing checks\n";
