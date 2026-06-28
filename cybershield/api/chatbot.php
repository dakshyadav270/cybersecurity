<?php
// api/chatbot.php  –  AI chatbot using Groq (free)
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['error' => 'Method not allowed'], 405);

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$userMsg = trim($input['message'] ?? '');
$history = $input['history'] ?? [];

if (!$userMsg) jsonOut(['error' => 'Message cannot be empty.'], 422);
if (strlen($userMsg) > 1000) jsonOut(['error' => 'Message too long.'], 422);

// Rate limit
$_SESSION['chat_count'] = ($_SESSION['chat_count'] ?? 0) + 1;
$_SESSION['chat_reset'] = $_SESSION['chat_reset'] ?? time();
if (time() - $_SESSION['chat_reset'] > 3600) {
    $_SESSION['chat_count'] = 1;
    $_SESSION['chat_reset'] = time();
}
if ($_SESSION['chat_count'] > 20) {
    jsonOut(['error' => 'Chat limit reached. Please wait an hour.'], 429);
}

// Build messages
$messages = [
    [
        'role'    => 'system',
        'content' => 'You are CyberBot, an expert cybersecurity assistant for the CyberShield awareness portal. Answer questions about cybersecurity threats, safe practices, password security, phishing, malware, VPNs, and related topics. Keep answers concise, friendly, and educational. If asked about something unrelated to cybersecurity, politely redirect. Use simple language suitable for students and beginners.'
    ]
];

foreach (array_slice($history, -8) as $h) {
    $role = ($h['role'] === 'assistant') ? 'assistant' : 'user';
    $messages[] = ['role' => $role, 'content' => (string)($h['content'] ?? '')];
}
$messages[] = ['role' => 'user', 'content' => $userMsg];

// Call Groq API
$payload = json_encode([
    'model'       => GROQ_MODEL,
    'messages'    => $messages,
    'max_tokens'  => 512,
    'temperature' => 0.7,
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
curl_close($ch);

if ($result === false || $httpCode !== 200) {
    jsonOut(['error' => 'Chatbot unavailable. Please try again later.'], 503);
}

$data  = json_decode($result, true);
$reply = $data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.';

// Save to chat history if logged in
if (isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    $ins = db()->prepare('INSERT INTO chat_history (user_id, role, message) VALUES (?, ?, ?)');
    $ins->execute([$uid, 'user',      $userMsg]);
    $ins->execute([$uid, 'assistant', $reply]);
}

bumpStat('chat_requests');
jsonOut(['reply' => $reply]);