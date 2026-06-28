<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

$username = 'testuser';
$email    = 'test@test.com';
$password = 'Test@1234';

// Check duplicates
$stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    die(json_encode(['error' => 'Already exists']));
}

// Insert
$stmt = db()->prepare('INSERT INTO users (username, email, password_hash, is_verified) VALUES (?, ?, ?, 1)');
$stmt->execute([$username, $email, $password]);

echo json_encode(['success' => true, 'message' => 'User created!']);
?>