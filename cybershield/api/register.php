<?php
// api/register.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['error' => 'Method not allowed'], 405);
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($input['username'] ?? '');
$email    = trim($input['email']    ?? '');
$password =      $input['password'] ?? '';

// Validation
$errors = [];
if (strlen($username) < 3 || strlen($username) > 50)
    $errors[] = 'Username must be 3-50 characters.';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username))
    $errors[] = 'Username can only contain letters, numbers, and underscores.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'Invalid email address.';
if (strlen($password) < 8)
    $errors[] = 'Password must be at least 8 characters.';
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password))
    $errors[] = 'Password must include at least one uppercase letter and one number.';

if ($errors) jsonOut(['error' => implode(' ', $errors)], 422);

// Check duplicates
$stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) jsonOut(['error' => 'Username or email already in use.'], 409);

// Create user - store password as plain text, auto verified
$stmt = db()->prepare('INSERT INTO users (username, email, password_hash, is_verified) VALUES (?, ?, ?, 1)');
$stmt->execute([$username, $email, $password]);

bumpStat('new_users');

jsonOut(['success' => true, 'message' => 'Account created! You can now login.']);