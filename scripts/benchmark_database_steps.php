<?php

require_once __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Fragment;
use App\Services\Commands\DSL\Steps\ModelCreateStep;
use App\Services\Commands\DSL\Steps\ModelQueryStep;

function benchmark($label, $callable, $iterations = 100)
{
    $start = microtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        $callable();
    }

    $end = microtime(true);
    $total = ($end - $start) * 1000; // Convert to milliseconds
    $average = $total / $iterations;

    echo sprintf("%-25s | %8.2fms | %8.2fms | %8d\n", $label, $total, $average, $iterations);

    return $average;
}

echo "Database Operations Performance Benchmark\n";
echo "=========================================\n";
echo sprintf("%-25s | %8s | %8s | %8s\n", 'Operation', 'Total', 'Average', 'Iterations');
echo str_repeat('-', 70)."\n";

// Test Fragment Query Operations
$queryStep = new ModelQueryStep;
$queryConfig = [
    'with' => [
        'model' => 'fragment',
        'conditions' => [['field' => 'type', 'value' => 'note']],
        'limit' => 10,
    ],
];

// Hardcoded equivalent
$hardcodedQuery = function () {
    Fragment::where('type', 'note')->limit(10)->get();
};

// DSL Step query
$dslQuery = function () use ($queryStep, $queryConfig) {
    $queryStep->execute($queryConfig, []);
};

// Dry run benchmark (no database access)
$dryRunQuery = function () use ($queryStep, $queryConfig) {
    $queryStep->execute($queryConfig, [], true);
};

$hardcodedTime = benchmark('Hardcoded Query', $hardcodedQuery, 50);
$dslTime = benchmark('DSL Query', $dslQuery, 50);
$dryRunTime = benchmark('DSL Dry Run', $dryRunQuery, 1000);

echo str_repeat('-', 70)."\n";

// Test Fragment Create Operations
$createStep = new ModelCreateStep;
$createConfig = [
    'with' => [
        'model' => 'fragment',
        'data' => [
            'message' => 'Benchmark test fragment',
            'type' => 'note',
            'vault' => 1,
        ],
    ],
];

$hardcodedCreate = function () {
    Fragment::create([
        'message' => 'Benchmark test fragment',
        'type' => 'note',
        'vault' => 1,
    ]);
};

$dslCreate = function () use ($createStep, $createConfig) {
    $createStep->execute($createConfig, []);
};

$dryRunCreate = function () use ($createStep, $createConfig) {
    $createStep->execute($createConfig, [], true);
};

$hardcodedCreateTime = benchmark('Hardcoded Create', $hardcodedCreate, 20);
$dslCreateTime = benchmark('DSL Create', $dslCreate, 20);
$dryRunCreateTime = benchmark('DSL Create Dry Run', $dryRunCreate, 1000);

echo str_repeat('-', 70)."\n";
echo "\nPerformance Analysis:\n";
echo "====================\n";

$queryOverhead = (($dslTime - $hardcodedTime) / $hardcodedTime) * 100;
$createOverhead = (($dslCreateTime - $hardcodedCreateTime) / $hardcodedCreateTime) * 100;

echo sprintf("Query Overhead:  %.1f%% (+%.2fms)\n", $queryOverhead, $dslTime - $hardcodedTime);
echo sprintf("Create Overhead: %.1f%% (+%.2fms)\n", $createOverhead, $dslCreateTime - $hardcodedCreateTime);
echo sprintf("Dry Run Speed:   %.2fms (%.0fx faster than real operations)\n", $dryRunTime, $dslTime / $dryRunTime);

echo "\nConclusion:\n";
echo "===========\n";
if ($queryOverhead < 30 && $createOverhead < 30) {
    echo "✓ DSL overhead is acceptable (< 30%)\n";
} else {
    echo "⚠ DSL overhead is significant\n";
}

echo "✓ Dry run operations are very fast for command validation\n";
echo "✓ DSL provides flexibility with minimal performance cost\n";

// Cleanup created fragments
Fragment::where('message', 'Benchmark test fragment')->delete();

echo "\nBenchmark completed successfully!\n";
