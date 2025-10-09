<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing /types Command Flow ===\n\n";

// Step 1: Check command registration
echo "1. Checking command registration...\n";
$isRegistered = \App\Services\CommandRegistry::isPhpCommand('types');
echo "   - Is 'types' registered? ".($isRegistered ? 'YES' : 'NO')."\n";

if ($isRegistered) {
    $class = \App\Services\CommandRegistry::getPhpCommand('types');
    echo "   - Class: {$class}\n";
    echo '   - Class exists? '.(class_exists($class) ? 'YES' : 'NO')."\n";
}

// Step 2: Test command execution
echo "\n2. Testing command execution...\n";
$command = new \App\Commands\TypeManagementCommand;
$result = $command->handle();
echo '   - Result: '.json_encode($result, JSON_PRETTY_PRINT)."\n";

// Step 3: Test API endpoint
echo "\n3. Testing API endpoint /api/types/admin...\n";
$controller = app(\App\Http\Controllers\TypeController::class);
$response = $controller->admin();
$data = json_decode($response->getContent(), true);
echo '   - Response status: '.$response->getStatusCode()."\n";
echo '   - Total types: '.($data['total'] ?? 0)."\n";

if (isset($data['data']) && count($data['data']) > 0) {
    echo '   - First type: '.json_encode($data['data'][0], JSON_PRETTY_PRINT)."\n";
} else {
    echo "   - No types returned!\n";
}

// Step 4: Check database directly
echo "\n4. Checking database directly...\n";
$types = \App\Models\FragmentTypeRegistry::userManageable()->get();
echo '   - Types in DB (userManageable): '.$types->count()."\n";
foreach ($types as $type) {
    echo "     - {$type->slug}: {$type->display_name} (enabled: {$type->is_enabled}, system: {$type->is_system}, hide_from_admin: {$type->hide_from_admin})\n";
}

// Step 5: Test full controller execution flow
echo "\n5. Testing CommandController execution...\n";
$request = \Illuminate\Http\Request::create('/api/commands/execute', 'POST', ['command' => '/types']);
$commandController = app(\App\Http\Controllers\CommandController::class);
$commandResponse = $commandController->execute($request);
$commandData = json_decode($commandResponse->getContent(), true);
echo '   - Success: '.($commandData['success'] ? 'YES' : 'NO')."\n";
echo '   - Type: '.($commandData['type'] ?? 'N/A')."\n";
echo '   - Component: '.($commandData['component'] ?? 'N/A')."\n";
echo '   - Data: '.json_encode($commandData['data'] ?? [])."\n";

echo "\n=== Test Complete ===\n";
