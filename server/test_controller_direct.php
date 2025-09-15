<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate authentication
use App\Models\User;
use App\Models\Chapter;
use App\Http\Controllers\AdminContentController;
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

echo "Testing controller method directly...\n";
echo "Using chapter ID: {$chapter->id}\n";

$testData = [
    'chapter_id' => $chapter->id,
    'title' => 'Test Lesson Direct',
    'content' => 'This is test lesson content created directly.',
    'concept_slug' => 'test-concept-direct'
];

try {
    $controller = new AdminContentController();
    $request = new \Illuminate\Http\Request();
    $request->merge($testData);

    $response = $controller->createLesson($request);
    $content = $response->getContent();

    echo "Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 200) {
        $data = json_decode($content, true);
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if ($data['success']) {
            echo "Lesson created with ID: " . ($data['data']['id'] ?? 'N/A') . "\n";
            echo "Lesson instructor_id: " . ($data['data']['instructor_id'] ?? 'N/A') . "\n";
        } else {
            echo "API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
            if (isset($data['error'])) {
                echo "Details: " . $data['error'] . "\n";
            }
        }
    } else {
        echo "HTTP Error: " . substr($content, 0, 500) . "\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

