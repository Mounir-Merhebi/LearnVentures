<?php

// Comprehensive API test script
echo "=== Full API Flow Test ===\n\n";

$baseUrl = 'http://127.0.0.1:8002/api/v0.1';
$email = 'test@example.com';
$password = 'password';

// Step 1: Login
echo "Step 1: Logging in...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/guest/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email, 'password' => $password]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
$loginData = json_decode($loginResponse, true);

if (!$loginData || !isset($loginData['data']['token'])) {
    echo "❌ Login failed!\n";
    echo "Response: $loginResponse\n";
    exit(1);
}

$token = $loginData['data']['token'];
echo "✅ Login successful, token: " . substr($token, 0, 20) . "...\n\n";

// Step 2: Create chat session
echo "Step 2: Creating chat session...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat/sessions');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['grade_id' => 2]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$sessionResponse = curl_exec($ch);
$sessionData = json_decode($sessionResponse, true);

if (!$sessionData || !isset($sessionData['data']['session_id'])) {
    echo "❌ Session creation failed!\n";
    echo "Response: $sessionResponse\n";
    exit(1);
}

$sessionId = $sessionData['data']['session_id'];
echo "✅ Chat session created, ID: $sessionId\n\n";

// Step 3: Send chat message
echo "Step 3: Sending chat message...\n";
$message = 'What is the quadratic formula?';
$messageData = [
    'session_id' => $sessionId,
    'message' => $message
];

curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat/messages');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));

$messageResponse = curl_exec($ch);
$messageData = json_decode($messageResponse, true);

curl_close($ch);

echo "📤 Sent: '$message'\n";
echo "📥 Response:\n";

if ($messageData && isset($messageData['data'])) {
    $response = $messageData['data']['response'];
    $contextUsed = $messageData['data']['context_chunks_used'] ?? 0;
    $gradeScope = $messageData['data']['grade_scope'] ?? 'Unknown';

    echo "   Response: $response\n";
    echo "   Context chunks used: $contextUsed\n";
    echo "   Grade scope: $gradeScope\n\n";

    if ($contextUsed > 0) {
        echo "✅ SUCCESS! API is working with Gemini embeddings!\n";
        echo "🎉 Your RAG chatbot is fully functional!\n";
    } else {
        echo "⚠️  WARNING: No context chunks used - this means:\n";
        echo "   1. No relevant embeddings found (unlikely, similarity test passed)\n";
        echo "   2. Session grade filtering issue\n";
        echo "   3. API returning cached/out-of-scope response\n";
        echo "\n🔍 Debug info:\n";
        echo "   Full API response: " . json_encode($messageData, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ Chat API failed!\n";
    echo "Response: $messageResponse\n";
}

echo "\n=== Test Complete ===\n";
