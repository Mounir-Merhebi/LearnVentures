<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate authentication
use App\Models\User;
use App\Models\Chapter;
use Illuminate\Support\Facades\Auth;

$adminUser = User::where('role', 'Admin')->first();
if (!$adminUser) {
    echo "No admin user found!\n";
    exit(1);
}

Auth::login($adminUser);

// Get a chapter to test with
$chapter = Chapter::with('subject')->first();
if (!$chapter) {
    echo "No chapters found!\n";
    exit(1);
}

echo "Testing lesson creation...\n";
echo "Using chapter ID: {$chapter->id}\n";
echo "Chapter subject instructor ID: {$chapter->subject->instructor_id}\n";

$testData = [
    'chapter_id' => $chapter->id,
    'title' => 'Test Lesson',
    'content' => 'This is test lesson content.',
    'concept_slug' => 'test-concept'
];

try {
    $request = \Illuminate\Http\Request::create('/api/v0.1/test-lesson-create', 'POST', $testData);
    $kernel = $app->make('Illuminate\Contracts\Http\Kernel');
    $response = $kernel->handle($request);
    $content = $response->getContent();

    echo "Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 200) {
        $data = json_decode($content, true);
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if ($data['success']) {
            echo "Lesson created with ID: " . ($data['data']['id'] ?? 'N/A') . "\n";
            echo "Lesson instructor_id: " . ($data['data']['instructor_id'] ?? 'N/A') . "\n";
        }
    } else {
        // Check if it's JSON error response
        $data = json_decode($content, true);
        if ($data && isset($data['success']) && !$data['success']) {
            echo "API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
            if (isset($data['error'])) {
                echo "Details: " . $data['error'] . "\n";
            }
        } else {
            echo "HTTP Error: " . substr($content, 0, 500) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
