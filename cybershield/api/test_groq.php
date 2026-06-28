<?php
require_once __DIR__ . '/../includes/config.php';

$payload = json_encode([
    'model' => GROQ_MODEL,
    'messages' => [
        ['role' => 'user', 'content' => 'Say hello in one sentence']
    ],
    'max_tokens' => 100,
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_TIMEOUT => 30,
]);

$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "<br>";
echo "Curl Error: " . $error . "<br>";
echo "Response: " . $result;
?>