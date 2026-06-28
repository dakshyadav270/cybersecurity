<?php
// api/contact.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['error' => 'Method not allowed'], 405);

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$name    = trim($input['name']    ?? '');
$email   = trim($input['email']   ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

// Validation
$errors = [];
if (strlen($name) < 2)                             $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Valid email required.';
if (strlen($subject) < 3)                          $errors[] = 'Subject is required.';
if (strlen($message) < 10)                         $errors[] = 'Message must be at least 10 characters.';
if ($errors) jsonOut(['error' => implode(' ', $errors)], 422);

// Spam protection: same email can't send more than 3 messages in 1 hour
$stmt = db()->prepare(
    "SELECT COUNT(*) FROM contact_messages
     WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
);
$stmt->execute([$email]);
if ((int)$stmt->fetchColumn() >= 3) {
    jsonOut(['error' => 'Too many messages. Please wait before sending another.'], 429);
}

// Save to DB
$userId = isLoggedIn() ? $_SESSION['user_id'] : null;
db()->prepare('INSERT INTO contact_messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)')
   ->execute([$userId, $name, $email, $subject, $message]);

bumpStat('messages_sent');

jsonOut(['success' => true, 'message' => 'Message received! We\'ll reply within 24 hours.']);
