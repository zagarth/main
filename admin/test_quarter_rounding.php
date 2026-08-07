<?php
/**
 * Test Quarter-Rounding Consistency
 * Compare PHP and JavaScript implementations
 */

require_once '../cadman-database/php/PricingCalculator.php';

echo "Quarter-Rounding Comparison Test\n";
echo "=================================\n\n";

// Test cases that should produce specific results
$testCases = [
    ['input' => 4281.34, 'expected' => 4281.50, 'description' => 'F120M test case - 34¢'],
    ['input' => 4282.79, 'expected' => 4282.75, 'description' => 'Expected F120M - 79¢'],
    ['input' => 100.10, 'expected' => 100.25, 'description' => '10¢ rounds to 25¢'],
    ['input' => 100.25, 'expected' => 100.25, 'description' => 'Exactly 25¢'],
    ['input' => 100.26, 'expected' => 100.50, 'description' => '26¢ rounds to 50¢'],
    ['input' => 100.50, 'expected' => 100.50, 'description' => 'Exactly 50¢'],
    ['input' => 100.51, 'expected' => 100.75, 'description' => '51¢ rounds to 75¢'],
    ['input' => 100.75, 'expected' => 100.75, 'description' => 'Exactly 75¢'],
    ['input' => 100.76, 'expected' => 101.00, 'description' => '76¢ rounds to next dollar'],
    ['input' => 100.99, 'expected' => 101.00, 'description' => '99¢ rounds to next dollar'],
    ['input' => 1533.25, 'expected' => 1533.25, 'description' => 'Stored F120M price'],
];

$calc = new PricingCalculator();
$passed = 0;
$failed = 0;

foreach ($testCases as $test) {
    $result = $calc->roundToQuarter($test['input']);
    $match = abs($result - $test['expected']) < 0.001;
    
    $status = $match ? "✓ PASS" : "✗ FAIL";
    if ($match) {
        $passed++;
    } else {
        $failed++;
    }
    
    printf(
        "%s | %-35s | Input: \$%8.2f → Result: \$%8.2f (Expected: \$%8.2f)\n",
        $status,
        $test['description'],
        $test['input'],
        $result,
        $test['expected']
    );
}

echo "\n";
echo "Summary: $passed passed, $failed failed\n";

// Now test the specific values from our pricing calculation
echo "\n\nDetailed Cents Breakdown for Key Values:\n";
echo "=========================================\n\n";

$detailedTests = [
    4281.34,
    4282.79,
    4281.00,
    4282.00,
];

foreach ($detailedTests as $value) {
    $dollars = floor($value);
    $fractionalPart = round(($value - $dollars) * 100000);
    $preciseCents = $fractionalPart / 1000;
    $result = $calc->roundToQuarter($value);
    
    echo sprintf("\$%.2f:\n", $value);
    echo "  Dollars: $dollars\n";
    echo "  Fractional Part: $fractionalPart\n";
    echo "  Precise Cents: $preciseCents\n";
    
    if ($preciseCents > 75) {
        echo "  Logic: >75 → round to next dollar\n";
    } elseif ($preciseCents > 50) {
        echo "  Logic: >50 → round to .75\n";
    } elseif ($preciseCents > 25) {
        echo "  Logic: >25 → round to .50\n";
    } else {
        echo "  Logic: ≤25 → round to .25\n";
    }
    
    echo sprintf("  Result: \$%.2f\n\n", $result);
}

// JavaScript equivalent code for comparison
echo "\n\nJavaScript Equivalent (for manual testing):\n";
echo "==========================================\n";
echo <<<'JAVASCRIPT'
function roundToQuarter(amount) {
    const hundredThousandths = Math.round(amount * 100000);
    const dollars = Math.floor(hundredThousandths / 100000);
    const fractionalPart = hundredThousandths % 100000;
    const preciseCents = fractionalPart / 1000;
    
    if (preciseCents > 75) {
        return dollars + 1.00;
    } else if (preciseCents > 50) {
        return dollars + 0.75;
    } else if (preciseCents > 25) {
        return dollars + 0.50;
    } else {
        return dollars + 0.25;
    }
}

// Test
console.log('4281.34 →', roundToQuarter(4281.34)); // Should be 4281.50
console.log('4282.79 →', roundToQuarter(4282.79)); // Should be 4282.75
JAVASCRIPT;

echo "\n";
?>
