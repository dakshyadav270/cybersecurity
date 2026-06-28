<?php
// api/verify_email.php
require_once __DIR__ . '/../includes/auth.php';

$token = trim($_GET['token'] ?? '');
if (!$token) {
    http_response_code(400);
    die('<h2>Invalid verification link.</h2>');
}

$stmt = db()->prepare('SELECT id, is_verified FROM users WHERE verify_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(400);
    die('<h2 style="font-family:sans-serif;color:#ff4d6d">Invalid or expired verification link.</h2>');
}
if ($user['is_verified']) {
    header('Location: /index.html?verified=already');
    exit;
}

db()->prepare('UPDATE users SET is_verified = 1, verify_token = NULL WHERE id = ?')
   ->execute([$user['id']]);

header('Location: /index.html?verified=1');
exit;
