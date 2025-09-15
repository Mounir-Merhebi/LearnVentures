<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing simple API route...\n";

$request = \Illuminate\Http\Request::create('/api/v0.1/test-simple', 'GET');
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle($request);
$content = $response->getContent();

echo "Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() == 200) {
    $data = json_decode($content, true);
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . ($data['message'] ?? 'N/A') . "\n";
} else {
    echo "Error: " . substr($content, 0, 500) . "\n";
}

