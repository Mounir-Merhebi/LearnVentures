<?php

// Test script to demonstrate the full chat API workflow
echo "=== Testing Chat API with Gemini Integration ===\n\n";

$baseUrl = 'http://127.0.0.1:8002/api/v0.1';

// Step 1: Login to get authentication token
echo "Step 1: Logging in...\n";
$loginData = [
    'email' => 'admin@example.com', // You may need to adjust this
    'password' => 'password'        // You may need to adjust this
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/guest/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
$loginData = json_decode($loginResponse, true);

if (!$loginData || !isset($loginData['data']['token'])) {
    echo "Login failed. Response: " . $loginResponse . "\n";
    echo "Note: You may need to create a user account first or adjust login credentials.\n";
    exit(1);
}

$token = $loginData['data']['token'];
echo "✓ Login successful, token: " . substr($token, 0, 20) . "...\n\n";

// Step 2: Create a chat session
echo "Step 2: Creating chat session...\n";
$sessionData = ['grade_id' => 2]; // Mathematics Grade 10

curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat/sessions');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sessionData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$sessionResponse = curl_exec($ch);
$sessionData = json_decode($sessionResponse, true);

if (!$sessionData || !isset($sessionData['data']['session_id'])) {
    echo "Session creation failed. Response: " . $sessionResponse . "\n";
    exit(1);
}

$sessionId = $sessionData['data']['session_id'];
echo "✓ Chat session created, ID: $sessionId\n\n";

// Step 3: Send a chat message
echo "Step 3: Sending chat message...\n";
$messageData = [
    'session_id' => $sessionId,
    'message' => 'What is the quadratic formula?'
];

curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat/messages');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));

$messageResponse = curl_exec($ch);
$messageData = json_decode($messageResponse, true);

curl_close($ch);

echo "Response:\n";
if ($messageData && isset($messageData['data'])) {
    echo "✓ Chat API working!\n";
    echo "  Response: " . substr($messageData['data']['response'], 0, 200) . "...\n";
    echo "  Context chunks used: " . ($messageData['data']['context_chunks_used'] ?? 0) . "\n";
    echo "  Grade scope: " . ($messageData['data']['grade_scope'] ?? 'Unknown') . "\n";
} else {
    echo "✗ Chat API failed. Response: " . $messageResponse . "\n";
}

echo "\n=== Test Complete ===\n";
