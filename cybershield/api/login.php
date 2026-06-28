<?php
// api/login.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['error' => 'Method not allowed'], 405);
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$login    = trim($input['login']    ?? '');
$password = trim($input['password'] ?? '');

if (!$login || !$password) jsonOut(['error' => 'Please enter username and password.'], 422);

// Find user by username or email
$stmt = db()->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1');
$stmt->execute([$login, $login]);
$user = $stmt->fetch();

// Compare plain text password
if (!$user || $password !== $user['password_hash']) {
    jsonOut(['error' => 'Invalid credentials.'], 401);
}
if ($user['is_banned']) {
    jsonOut(['error' => 'Your account has been suspended.'], 403);
}
if (!$user['is_verified']) {
    jsonOut(['error' => 'Please verify your email before logging in.'], 403);
}

// Start session
session_regenerate_id(true);
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

// Update last_login
db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

jsonOut([
    'success' => true,
    'user'    => [
        'id'         => $user['id'],
        'username'   => $user['username'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'badge'      => $user['badge_level'],
        'best_score' => $user['best_score'],
        'quizzes'    => $user['quizzes_taken'],
    ],
]);