<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

$login = 'testuser';
$password = 'Test@1234';

$stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$login]);
$user = $stmt->fetch();

if (!$user) {
    die(json_encode(['error' => 'User not found']));
}

echo json_encode([
    'found' => true,
    'username' => $user['username'],
    'password_in_db' => $user['password_hash'],
    'password_entered' => $password,
    'match' => ($password === $user['password_hash'])
]);
?>